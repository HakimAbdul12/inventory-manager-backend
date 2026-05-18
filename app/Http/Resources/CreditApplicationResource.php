<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'lead_id'          => $this->lead_id,
            'token'            => $this->token,
            'status'           => $this->status,
            'is_active'        => $this->is_active,
            'public_url'       => $this->public_url,

            // Masked data — SSN shows last 4 only
            'application_data' => $this->masked_application_data,

            // Timestamps
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'opened_at'        => $this->opened_at?->toIso8601String(),
            'submitted_at'     => $this->submitted_at?->toIso8601String(),
            'reactivated_at'   => $this->reactivated_at?->toIso8601String(),

            // E-signature
            'esignature_name'  => $this->esignature_name,
            'esignature_date'  => $this->esignature_date?->toDateString(),

            // Users
            'created_by'       => $this->created_by,
            'created_by_user'  => $this->whenLoaded('createdByUser', fn () => [
                'id'   => $this->createdByUser->id,
                'name' => $this->createdByUser->name,
            ]),
            'reactivated_by'      => $this->reactivated_by,
            'reactivated_by_user' => $this->whenLoaded('reactivatedByUser', fn () => [
                'id'   => $this->reactivatedByUser->id,
                'name' => $this->reactivatedByUser->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
