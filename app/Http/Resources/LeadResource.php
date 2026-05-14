<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,

            // Source tracking
            'source' => $this->source,
            'source_type' => $this->source_type,
            'source_name' => $this->source_name,
            'provider_name' => $this->provider_name,

            // Origin
            'recorded_by_type' => $this->recorded_by_type,
            'recorded_by_id' => $this->recorded_by_id,

            // Status & lifecycle
            'status' => $this->status,
            'lifecycle_stage' => $this->lifecycle_stage,
            'intent' => $this->intent,

            // Assignment
            'assigned_to' => $this->assigned_to,
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
                'avatar' => $this->assignedUser->avatar,
            ]),

            // Vehicle interest
            'interested_vehicle_id' => $this->interested_vehicle_id,
            'interested_vehicle' => $this->whenLoaded('interestedVehicle', fn () => [
                'id' => $this->interestedVehicle->id,
                'title' => $this->interestedVehicle->title,
                'status' => $this->interestedVehicle->status,
                'primary_image' => $this->interestedVehicle->images->firstWhere('is_primary', true)?->path ?? null,
            ]),
            'vehicle_details' => $this->vehicle_details,

            // Chat linkage
            'conversation_id' => $this->conversation_id,

            // Metadata
            'external_reference_id' => $this->external_reference_id,
            'notes' => $this->notes,

            // Counts (when loaded)
            'deals_count' => $this->whenCounted('deals'),
            'inventory_items_count' => $this->whenCounted('inventoryItems'),
            'status_history_count' => $this->whenCounted('statusHistory'),

            // Relationships (when loaded)
            'deals' => $this->whenLoaded('deals'),
            'inventory_items' => $this->whenLoaded('inventoryItems'),
            'status_history' => $this->whenLoaded('statusHistory'),

            // Timestamps
            'last_contacted_at' => $this->last_contacted_at?->toIso8601String(),
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
