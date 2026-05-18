<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditApplicationResource;
use App\Mail\CrmLeadEmail;
use App\Models\CreditApplication;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\Notification;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreditApplicationController extends Controller
{
    // ── Internal (Authenticated) Endpoints ────────────────────

    /**
     * Get the credit application for a lead.
     */
    public function show(string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $application = $lead->creditApplication()
            ->with(['createdByUser:id,name', 'reactivatedByUser:id,name'])
            ->first();

        if (!$application) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => new CreditApplicationResource($application),
        ]);
    }

    /**
     * Create a credit application for a lead.
     */
    public function store(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        if ($lead->creditApplication) {
            return response()->json([
                'message' => 'A credit application already exists for this lead.',
            ], 422);
        }

        $vehicleInterest = [];
        if ($lead->interestedVehicle) {
            $vehicleInterest = [
                'year'  => (string) ($lead->interestedVehicle->year ?? ''),
                'make'  => $lead->interestedVehicle->make ?? '',
                'model' => $lead->interestedVehicle->model ?? '',
                'price' => (string) ($lead->interestedVehicle->price ?? ''),
            ];
        } elseif (is_array($lead->vehicle_details)) {
            $vehicleInterest = [
                'year'  => (string) ($lead->vehicle_details['year'] ?? ''),
                'make'  => $lead->vehicle_details['make'] ?? '',
                'model' => $lead->vehicle_details['model'] ?? '',
                'price' => (string) ($lead->vehicle_details['price'] ?? ''),
            ];
        }

        $application = CreditApplication::create([
            'tenant_id'  => $lead->tenant_id,
            'lead_id'    => $lead->id,
            'token'      => Str::random(64),
            'status'     => CreditApplication::STATUS_NOT_SENT,
            'created_by' => $request->user()->id,
            'application_data' => [
                'personal_info' => [
                    'first_name' => $lead->first_name ?? '',
                    'last_name'  => $lead->last_name ?? '',
                    'email'      => $lead->email ?? '',
                    'phone'      => $lead->phone ?? '',
                ],
                'vehicle_interest' => $vehicleInterest,
            ],
        ]);

        ActivityLogger::record(
            'credit_application.created',
            $application,
            "Credit application created for lead: {$lead->full_name}",
        );

        $application->load(['createdByUser:id,name']);

        return response()->json([
            'message' => 'Credit application created successfully.',
            'data'    => new CreditApplicationResource($application),
        ], 201);
    }

    /**
     * Update the credit application data internally.
     */
    public function update(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        $application = $lead->creditApplication;

        if (!$application) {
            return response()->json(['message' => 'No credit application found.'], 404);
        }

        $validated = $request->validate([
            'application_data' => 'required|array',
        ]);

        $application->update([
            'application_data' => $validated['application_data'],
        ]);

        ActivityLogger::record(
            'credit_application.updated',
            $application,
            "Credit application data updated for lead: {$lead->full_name}",
        );

        return response()->json([
            'message' => 'Credit application updated successfully.',
            'data'    => new CreditApplicationResource($application),
        ]);
    }

    /**
     * Send the credit application link via email.
     */
    public function sendEmail(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        $application = $lead->creditApplication;

        if (!$application) {
            return response()->json(['message' => 'No credit application found.'], 404);
        }

        if (!$lead->email) {
            return response()->json(['message' => 'Lead has no email address on file.'], 422);
        }

        // Find the system template for credit application email
        $template = MessageTemplate::where('system_key', 'credit_application_link')
            ->where('channel', 'email')
            ->first();

        $publicUrl = $application->public_url;
        $dealershipName = app()->bound('current_tenant') ? app('current_tenant')->name : 'Our Dealership';

        if ($template) {
            $subject = str_replace(
                ['{{customer_name}}', '{{dealership_name}}', '{{application_link}}'],
                [$lead->full_name, $dealershipName, $publicUrl],
                $template->subject ?? 'Complete Your Credit Application',
            );
            $body = str_replace(
                ['{{customer_name}}', '{{dealership_name}}', '{{application_link}}'],
                [$lead->full_name, $dealershipName, "<a href=\"{$publicUrl}\">{$publicUrl}</a>"],
                $template->body_html ?? $template->body,
            );
        } else {
            $subject = "Complete Your Credit Application — {$dealershipName}";
            $body = "<p>Hi {$lead->full_name},</p>"
                . "<p>Please complete your credit application by clicking the link below:</p>"
                . "<p><a href=\"{$publicUrl}\">{$publicUrl}</a></p>"
                . "<p>Thank you,<br>{$dealershipName}</p>";
        }

        Mail::to($lead->email)->send(new CrmLeadEmail(
            emailSubject: $subject,
            bodyHtml: $body,
            bodyText: strip_tags($body),
        ));

        $application->markAsSent();

        ActivityLogger::record(
            'credit_application.sent_email',
            $application,
            "Credit application link emailed to {$lead->email}",
        );

        return response()->json(['message' => 'Credit application link sent via email.']);
    }

    /**
     * Send the credit application link via SMS (simulation).
     */
    public function sendSms(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        $application = $lead->creditApplication;

        if (!$application) {
            return response()->json(['message' => 'No credit application found.'], 404);
        }

        if (!$lead->phone) {
            return response()->json(['message' => 'Lead has no phone number on file.'], 422);
        }

        $publicUrl = $application->public_url;
        $dealershipName = app()->bound('current_tenant') ? app('current_tenant')->name : 'Our Dealership';

        // Simulation log — replace with actual SMS provider when tenant configures one
        \Log::info('SMS Simulation', [
            'to'      => $lead->phone,
            'message' => "Hi {$lead->first_name}, please complete your credit application: {$publicUrl} — {$dealershipName}",
        ]);

        $application->markAsSent();

        ActivityLogger::record(
            'credit_application.sent_sms',
            $application,
            "Credit application link sent via SMS to {$lead->phone} (simulated)",
        );

        return response()->json(['message' => 'Credit application link sent via SMS.']);
    }

    /**
     * Reactivate an inactive credit application link.
     */
    public function reactivate(Request $request, string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        $application = $lead->creditApplication;

        if (!$application) {
            return response()->json(['message' => 'No credit application found.'], 404);
        }

        if ($application->is_active) {
            return response()->json(['message' => 'Application link is already active.'], 422);
        }

        // Generate a new token and reactivate
        $application->update([
            'token'          => Str::random(64),
            'is_active'      => true,
            'status'         => CreditApplication::STATUS_NOT_SENT,
            'reactivated_by' => $request->user()->id,
            'reactivated_at' => now(),
            // Clear old submission data for a fresh start
            'application_data' => null,
            'submitted_at'     => null,
            'esignature_name'  => null,
            'esignature_date'  => null,
        ]);

        ActivityLogger::record(
            'credit_application.reactivated',
            $application,
            "Credit application reactivated by {$request->user()->name}",
            ['reactivated_by' => $request->user()->id],
        );

        $application->load(['createdByUser:id,name', 'reactivatedByUser:id,name']);

        return response()->json([
            'message' => 'Credit application reactivated successfully.',
            'data'    => new CreditApplicationResource($application),
        ]);
    }

    /**
     * Generate and download a PDF summary of the submitted application.
     */
    public function downloadPdf(string $leadId)
    {
        $lead = Lead::findOrFail($leadId);
        $application = $lead->creditApplication;

        if (!$application || !$application->isSubmitted()) {
            return response()->json(['message' => 'No submitted application found.'], 404);
        }

        $data = $application->masked_application_data;
        $dealershipName = app()->bound('current_tenant') ? app('current_tenant')->name : 'Dealership';

        $pdf = Pdf::loadView('pdf.credit-application', [
            'application'    => $application,
            'lead'           => $lead,
            'data'           => $data,
            'dealershipName' => $dealershipName,
        ]);

        $filename = "credit-application-{$lead->last_name}-{$lead->first_name}.pdf";

        return $pdf->download($filename);
    }

    // ── Public (Unauthenticated) Endpoints ───────────────────

    /**
     * Validate a public token and return form config.
     */
    public function publicShow(string $token): JsonResponse
    {
        $application = CreditApplication::withoutGlobalScope('tenant')
            ->where('token', $token)
            ->first();

        if (!$application) {
            return response()->json(['message' => 'Invalid application link.'], 404);
        }

        if (!$application->is_active) {
            return response()->json([
                'message' => 'This application link is no longer active.',
                'status'  => 'inactive',
            ], 410);
        }

        if ($application->isSubmitted()) {
            return response()->json([
                'message' => 'This application has already been submitted.',
                'status'  => 'submitted',
            ], 410);
        }

        // Mark as opened on first view
        if (in_array($application->status, [CreditApplication::STATUS_NOT_SENT, CreditApplication::STATUS_SENT])) {
            $application->markAsOpened();

            // Notify lead owner
            $lead = $application->lead()->withoutGlobalScope('tenant')->first();
            if ($lead && $lead->assigned_to) {
                Notification::send(
                    userId: $lead->assigned_to,
                    type: 'credit_application.opened',
                    title: 'Credit Application Opened',
                    body: "{$lead->full_name} has opened their credit application.",
                    actionUrl: "/dashboard/crm/{$lead->id}",
                    subject: $application,
                    tenantId: $application->tenant_id,
                );
            }
        }

        $tenant = $application->tenant()->withoutGlobalScope('tenant')->first();

        return response()->json([
            'data' => [
                'token'            => $application->token,
                'status'           => $application->status,
                'dealership'       => [
                    'name' => $tenant?->name ?? 'Dealership',
                    'logo' => $tenant?->logo_url ?? null,
                ],
                'application_data' => $application->application_data,
            ],
        ]);
    }

    /**
     * Submit the completed credit application form.
     */
    public function publicSubmit(Request $request, string $token): JsonResponse
    {
        $application = CreditApplication::withoutGlobalScope('tenant')
            ->where('token', $token)
            ->first();

        if (!$application) {
            return response()->json(['message' => 'Invalid application link.'], 404);
        }

        if (!$application->is_active) {
            return response()->json(['message' => 'This application link is no longer active.'], 410);
        }

        if ($application->isSubmitted()) {
            return response()->json(['message' => 'This application has already been submitted.'], 410);
        }

        $validated = $request->validate([
            'application_data'     => 'required|array',
            'esignature_name'      => 'required|string|max:255',
            'application_data.personal_info'     => 'required|array',
            'application_data.personal_info.first_name' => 'required|string',
            'application_data.personal_info.last_name'  => 'required|string',
        ]);

        $application->markAsSubmitted(
            applicationData: $validated['application_data'],
            esignatureName: $validated['esignature_name'],
        );

        // Notify lead owner
        $lead = $application->lead()->withoutGlobalScope('tenant')->first();
        if ($lead && $lead->assigned_to) {
            Notification::send(
                userId: $lead->assigned_to,
                type: 'credit_application.submitted',
                title: 'Credit Application Submitted',
                body: "{$lead->full_name} has submitted their credit application.",
                actionUrl: "/dashboard/crm/{$lead->id}",
                subject: $application,
                tenantId: $application->tenant_id,
            );
        }

        return response()->json([
            'message' => 'Credit application submitted successfully. Thank you!',
        ]);
    }
}
