<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Mail\CrmLeadEmail;
use App\Models\Lead;
use App\Models\LeadCommunication;
use App\Models\LeadStatusHistory;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CrmCommunicationController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {}

    /**
     * List communications for a lead.
     */
    public function index(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $query = $lead->communications()
            ->with('sentByUser:id,name,avatar')
            ->orderBy('created_at', 'desc');

        if ($request->filled('channel')) {
            $query->byChannel($request->channel);
        }

        $communications = $query->paginate($request->input('per_page', 20));

        return response()->json($communications);
    }

    /**
     * Store a new communication (log a call, send email/sms).
     */
    public function store(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $validated = $request->validate([
            'channel' => 'required|string|in:' . implode(',', LeadCommunication::CHANNELS),
            'direction' => 'sometimes|string|in:outbound,inbound',
            'to_address' => 'required|string|max:255',
            'from_address' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'body_html' => 'nullable|string',
            'status' => 'sometimes|string|in:draft,sent,delivered,failed,logged',
            'metadata' => 'nullable|array',
            'metadata.disposition' => 'nullable|string',
            'metadata.duration' => 'nullable|integer',
            'metadata.call_notes' => 'nullable|string',
        ]);

        // Resolve variables for the body/subject
        $resolvedVars = $this->buildVariables($lead, $request->user());
        $resolvedBody = $this->replaceVariables($validated['body'] ?? '', $resolvedVars);
        $resolvedBodyHtml = $this->replaceVariables($validated['body_html'] ?? '', $resolvedVars);
        $resolvedSubject = $this->replaceVariables($validated['subject'] ?? '', $resolvedVars);

        $communication = LeadCommunication::create([
            'lead_id' => $lead->id,
            'channel' => $validated['channel'],
            'direction' => $validated['direction'] ?? 'outbound',
            'from_address' => $validated['from_address'] ?? config('mail.from.address'),
            'to_address' => $validated['to_address'],
            'subject' => $resolvedSubject ?: null,
            'body' => $resolvedBody ?: null,
            'body_html' => $resolvedBodyHtml ?: null,
            'status' => $validated['status'] ?? 'sent',
            'metadata' => $validated['metadata'] ?? null,
            'sent_by' => $request->user()->id,
            'sent_at' => now(),
        ]);

        // Actually send the email
        if ($validated['channel'] === 'email' && ($validated['status'] ?? 'sent') === 'sent') {
            try {
                Mail::to($validated['to_address'])->send(
                    new CrmLeadEmail(
                        emailSubject: $resolvedSubject,
                        bodyHtml: $resolvedBodyHtml ?: nl2br(e($resolvedBody)),
                        bodyText: $resolvedBody,
                        senderName: $request->user()->name,
                    )
                );
                $communication->update(['status' => 'sent']);
            } catch (\Throwable $e) {
                $communication->update([
                    'status' => 'failed',
                    'metadata' => array_merge($communication->metadata ?? [], ['error' => $e->getMessage()]),
                ]);
                // Don't fail the whole request — the communication is logged
                \Log::error('CRM email send failed', ['lead' => $lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Build descriptive notes for the timeline
        $channelLabels = ['phone' => 'Phone Call', 'email' => 'Email', 'sms' => 'SMS'];
        $channelLabel = $channelLabels[$validated['channel']] ?? $validated['channel'];
        
        $notes = "{$channelLabel} sent to {$validated['to_address']}";
        if (!empty($validated['subject'])) {
            $notes .= "\nSubject: {$validated['subject']}";
        }
        if ($validated['channel'] === 'phone' && !empty($validated['metadata']['disposition'])) {
            $notes .= "\nDisposition: " . str_replace('_', ' ', $validated['metadata']['disposition']);
        }
        if ($validated['channel'] === 'phone' && !empty($validated['metadata']['call_notes'])) {
            $notes .= "\n" . $validated['metadata']['call_notes'];
        }
        if ($validated['channel'] !== 'phone' && !empty($validated['body'])) {
            // For SMS/email, include a snippet
            $snippet = mb_substr(strip_tags($validated['body']), 0, 200);
            if (mb_strlen(strip_tags($validated['body'])) > 200) {
                $snippet .= '...';
            }
            $notes .= "\n" . $snippet;
        }

        // Record in timeline
        LeadStatusHistory::create([
            'lead_id' => $lead->id,
            'from_status' => $lead->status,
            'to_status' => $lead->status,
            'changed_by' => $request->user()->id,
            'notes' => $notes,
        ]);

        // Auto-transition from 'new' to 'contacted'
        if ($lead->status === Lead::STATUS_NEW) {
            $lead->transitionStatus(Lead::STATUS_CONTACTED, $request->user()->id, "Auto-transitioned: first {$channelLabel} sent");
        }

        // Update last_contacted_at
        $lead->update([
            'last_contacted_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->activityLogger->log(
            "lead.communication.{$validated['channel']}",
            $lead,
            "{$channelLabel} logged for lead",
            ['channel' => $validated['channel'], 'to' => $validated['to_address']]
        );

        return response()->json([
            'message' => "{$channelLabel} logged successfully.",
            'data' => $communication->load('sentByUser:id,name,avatar'),
        ], 201);
    }

    /**
     * Resolve template variables for a lead.
     */
    public function resolveVariables(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::with(['interestedVehicle.images', 'assignedUser'])->findOrFail($leadId);
        $variables = $this->buildVariables($lead, $request->user());

        return response()->json(['data' => $variables]);
    }

    /**
     * Build the variable map for a given lead.
     */
    private function buildVariables(Lead $lead, $user): array
    {
        $lead->loadMissing(['interestedVehicle.images', 'assignedUser']);
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        $vehicle = $lead->interestedVehicle;
        $vehicleData = $vehicle?->generated_data ?? [];

        return [
            'lead_first_name' => $lead->first_name ?? '',
            'lead_last_name' => $lead->last_name ?? '',
            'lead_full_name' => $lead->full_name,
            'lead_email' => $lead->email ?? '',
            'lead_phone' => $lead->phone ?? '',
            'vehicle_title' => $vehicle?->title ?? '',
            'vehicle_price' => isset($vehicleData['price']) ? '$' . number_format($vehicleData['price']) : '',
            'vehicle_vin' => $vehicleData['vin'] ?? '',
            'vehicle_stock_number' => $vehicleData['stock_number'] ?? '',
            'vehicle_year' => $vehicleData['year'] ?? '',
            'vehicle_make' => $vehicleData['make'] ?? '',
            'vehicle_model' => $vehicleData['model'] ?? '',
            'vehicle_image_url' => $vehicle ? ($vehicle->images->firstWhere('is_primary', true)?->url ?? '') : '',
            'tenant_name' => $tenant?->name ?? '',
            'sender_name' => $user->name ?? '',
            'sender_email' => $user->email ?? '',
            'current_date' => now()->format('F j, Y'),
            'current_time' => now()->format('g:i A'),
        ];
    }

    /**
     * Replace {{variable}} placeholders in text.
     */
    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{{$key}}}", (string) $value, $text);
        }
        return $text;
    }
}
