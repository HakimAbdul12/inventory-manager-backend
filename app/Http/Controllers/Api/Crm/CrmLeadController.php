<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CrmLeadController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {}

    /**
     * List leads with CRM filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => 'sometimes|string',
            'source_type' => 'sometimes|string',
            'assigned_to' => 'sometimes|integer',
            'lifecycle_stage' => 'sometimes|string',
            'search' => 'sometimes|string|max:255',
            'sort_by' => 'sometimes|in:created_at,last_activity_at,first_name,status',
            'sort_dir' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Lead::query()
            ->with(['assignedUser', 'interestedVehicle.images'])
            ->withCount(['deals', 'inventoryItems']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('source_type')) {
            $query->bySource($request->source_type);
        }

        if ($request->filled('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->filled('lifecycle_stage')) {
            $query->byLifecycle($request->lifecycle_stage);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $leads = $query->paginate($request->input('per_page', 20));

        return LeadResource::collection($leads);
    }

    /**
     * Create a new lead manually.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'source_type' => 'required|string|in:' . implode(',', Lead::SOURCE_TYPES),
            'source_name' => 'nullable|string|max:255',
            'intent' => 'nullable|string|in:' . implode(',', Lead::INTENTS),
            'assigned_to' => 'nullable|integer|exists:users,id',
            'interested_vehicle_id' => 'nullable|uuid|exists:inventory_items,id',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:' . implode(',', Lead::STATUSES),
        ]);

        $validated['recorded_by_type'] = Lead::RECORDED_BY_USER;
        $validated['recorded_by_id'] = $request->user()->id;
        $validated['last_activity_at'] = now();

        $prospect = null;
        if (!empty($validated['email'])) {
            $prospect = \App\Models\Prospect::where('email', $validated['email'])->first();
        }
        if (!$prospect && !empty($validated['phone'])) {
            $prospect = \App\Models\Prospect::where('phone', $validated['phone'])->first();
        }

        if (!$prospect) {
            $prospect = \App\Models\Prospect::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'assigned_to' => $validated['assigned_to'] ?? null,
            ]);
        }

        $validated['prospect_id'] = $prospect->id;

        $lead = Lead::create($validated);

        $this->activityLogger->log(
            'lead.created',
            $lead,
            'Lead created manually',
            ['source_type' => $lead->source_type]
        );

        return response()->json([
            'message' => 'Lead created successfully.',
            'data' => new LeadResource($lead->load(['assignedUser', 'interestedVehicle.images', 'prospect'])),
        ], 201);
    }

    /**
     * Show a single lead with full details.
     */
    public function show(string $id): JsonResponse
    {
        $lead = Lead::with([
            'prospect',
            'assignedUser',
            'interestedVehicle.images',
            'inventoryItems.images',
            'deals',
            'statusHistory.changedByUser',
            'conversation.messages',
            'recordedBy',
        ])->withCount(['deals', 'inventoryItems'])->findOrFail($id);

        return response()->json([
            'data' => new LeadResource($lead),
        ]);
    }

    /**
     * Update a lead.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'source_type' => 'sometimes|string|in:' . implode(',', Lead::SOURCE_TYPES),
            'source_name' => 'sometimes|nullable|string|max:255',
            'intent' => 'sometimes|nullable|string|in:' . implode(',', Lead::INTENTS),
            'assigned_to' => 'sometimes|nullable|integer|exists:users,id',
            'interested_vehicle_id' => 'sometimes|nullable|uuid|exists:inventory_items,id',
            'notes' => 'sometimes|nullable|string',
            'lifecycle_stage' => 'sometimes|string|in:' . implode(',', Lead::LIFECYCLE_STAGES),
        ]);

        $lead->update($validated);

        return response()->json([
            'message' => 'Lead updated successfully.',
            'data' => new LeadResource($lead->fresh(['assignedUser', 'interestedVehicle.images'])),
        ]);
    }

    /**
     * Delete a lead.
     */
    public function destroy(string $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully.']);
    }

    /**
     * Transition lead status with audit trail.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', Lead::STATUSES),
            'notes' => 'nullable|string',
        ]);

        $lead = Lead::findOrFail($id);
        $lead->transitionStatus(
            $validated['status'],
            $request->user()->id,
            $validated['notes'] ?? null
        );

        $this->activityLogger->log(
            'lead.status_changed',
            $lead,
            "Status changed to {$validated['status']}",
            ['from' => $lead->getOriginal('status'), 'to' => $validated['status']]
        );

        return response()->json([
            'message' => 'Lead status updated.',
            'data' => new LeadResource($lead->fresh(['assignedUser', 'statusHistory'])),
        ]);
    }

    /**
     * Assign lead to a salesperson.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $lead = Lead::findOrFail($id);
        $lead->update([
            'assigned_to' => $validated['assigned_to'],
            'last_activity_at' => now(),
        ]);

        $this->activityLogger->log(
            'lead.assigned',
            $lead,
            'Lead assigned to salesperson',
            ['assigned_to' => $validated['assigned_to']]
        );

        return response()->json([
            'message' => 'Lead assigned successfully.',
            'data' => new LeadResource($lead->fresh(['assignedUser'])),
        ]);
    }

    /**
     * Get lead status history (Road-to-Sale timeline).
     */
    public function statusTimeline(string $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);

        $history = $lead->statusHistory()
            ->with('changedByUser:id,name,avatar')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'status_change',
                    'from_status' => $item->from_status,
                    'to_status' => $item->to_status,
                    'notes' => $item->notes,
                    'user' => $item->changedByUser,
                    'created_at' => $item->created_at,
                ];
            });

        $communications = $lead->communications()
            ->with('sentByUser:id,name,avatar')
            ->get()
            ->map(function ($comm) {
                return [
                    'id' => $comm->id,
                    'type' => 'communication',
                    'channel' => $comm->channel,
                    'direction' => $comm->direction,
                    'to_address' => $comm->to_address,
                    'subject' => $comm->subject,
                    'body' => $comm->body,
                    'status' => $comm->status,
                    'metadata' => $comm->metadata,
                    'user' => $comm->sentByUser,
                    'created_at' => $comm->created_at,
                ];
            });

        // Merge and sort descending by created_at
        $timeline = $history->concat($communications)
            ->sortByDesc('created_at')
            ->values();

        return response()->json(['data' => $timeline]);
    }

    /**
     * List vehicles the lead is interested in.
     */
    public function vehicles(string $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);

        $vehicles = $lead->inventoryItems()
            ->with('images')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'status' => $item->status,
                'price' => $item->generated_data['price'] ?? null,
                'stock_number' => $item->generated_data['stock_number'] ?? null,
                'vin' => $item->generated_data['vin'] ?? null,
                'primary_image' => $item->images->firstWhere('is_primary', true)?->path ?? null,
                'is_primary_interest' => $item->id === $lead->interested_vehicle_id,
            ]);

        return response()->json(['data' => $vehicles]);
    }

    /**
     * Add a vehicle to the lead's interest list.
     */
    public function addVehicle(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|uuid|exists:inventory_items,id',
            'is_primary' => 'sometimes|boolean',
        ]);

        $lead = Lead::findOrFail($id);
        $oldVehicleId = $lead->interested_vehicle_id;
        
        $lead->inventoryItems()->syncWithoutDetaching([$validated['vehicle_id']]);

        // If explicitly requested or if it's the only vehicle, make it primary
        if ($request->boolean('is_primary') || !$oldVehicleId) {
            $lead->update(['interested_vehicle_id' => $validated['vehicle_id']]);
        }
        
        // Log the activity
        $newVehicle = \App\Models\InventoryItem::find($validated['vehicle_id']);
        $oldVehicle = $oldVehicleId ? \App\Models\InventoryItem::find($oldVehicleId) : null;
        
        $notes = "Assigned vehicle: " . $newVehicle->title . " (#" . substr($newVehicle->id, 0, 8) . ")";
        if ($oldVehicle && $oldVehicle->id !== $newVehicle->id) {
             $notes .= "\nPreviously assigned primary vehicle: " . $oldVehicle->title;
        }

        \App\Models\LeadStatusHistory::create([
            'lead_id' => $lead->id,
            'from_status' => $lead->status,
            'to_status' => $lead->status,
            'changed_by' => $request->user()?->id,
            'notes' => $notes,
        ]);

        return response()->json(['message' => 'Vehicle added to interest list.']);
    }

    public function removeVehicle(Request $request, string $id, string $vehicleId): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $vehicle = \App\Models\InventoryItem::find($vehicleId);
        
        $lead->inventoryItems()->detach($vehicleId);

        if ($lead->interested_vehicle_id === $vehicleId) {
            $lead->update(['interested_vehicle_id' => null]);
        }
        
        if ($vehicle) {
            \App\Models\LeadStatusHistory::create([
                'lead_id' => $lead->id,
                'from_status' => $lead->status,
                'to_status' => $lead->status,
                'changed_by' => $request->user()?->id,
                'notes' => "Removed vehicle: " . $vehicle->title . " (#" . substr($vehicle->id, 0, 8) . ")",
            ]);
        }

        return response()->json(['message' => 'Vehicle removed from interest list.']);
    }

    /**
     * Get available CRM metadata (for filter dropdowns).
     */
    public function metadata(): JsonResponse
    {
        return response()->json([
            'statuses' => Lead::STATUSES,
            'source_types' => Lead::SOURCE_TYPES,
            'lifecycle_stages' => Lead::LIFECYCLE_STAGES,
            'intents' => Lead::INTENTS,
        ]);
    }
}
