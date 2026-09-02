<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;

class GetLeadTool implements McpTool
{
    public function name(): string
    {
        return 'get_lead';
    }

    public function description(): string
    {
        return 'Get full details of a CRM lead by its ID, including contact info, status timeline, associated vehicles, and communication history.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'The lead UUID.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'crm.leads.view';
    }

    public function category(): string
    {
        return 'CRM';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $lead = Lead::where('tenant_id', $tenant->id)
            ->where('id', $args['id'])
            ->with([
                'assignedUser:id,name',
                'statusHistory',
                'vehicles:id,generated_data',
                'communications' => fn($q) => $q->orderBy('created_at', 'desc')->limit(10),
            ])
            ->first();

        if (!$lead) {
            return [
                ['type' => 'text', 'text' => "Lead not found with ID: {$args['id']}"],
            ];
        }

        $result = [
            'id' => $lead->id,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => $lead->status,
            'source_type' => $lead->source_type,
            'lifecycle_stage' => $lead->lifecycle_stage,
            'assigned_to' => $lead->assignedUser?->name,
            'notes' => $lead->notes,
            'metadata' => $lead->metadata,
            'status_timeline' => $lead->statusHistory->map(fn($sh) => [
                'from_status' => $sh->from_status,
                'to_status' => $sh->to_status,
                'changed_at' => $sh->created_at->toIso8601String(),
                'changed_by' => $sh->changed_by,
            ])->toArray(),
            'vehicles_of_interest' => $lead->vehicles->map(function ($v) {
                $data = $v->generated_data ?? [];
                return [
                    'id' => $v->id,
                    'title' => ($data['year'] ?? '') . ' ' . ($data['make'] ?? '') . ' ' . ($data['model'] ?? ''),
                    'price' => $data['price'] ?? null,
                ];
            })->toArray(),
            'recent_communications' => $lead->communications->map(fn($c) => [
                'type' => $c->type,
                'direction' => $c->direction,
                'subject' => $c->subject,
                'content_preview' => $c->content ? substr($c->content, 0, 200) : null,
                'sent_at' => $c->created_at->toIso8601String(),
            ])->toArray(),
            'created_at' => $lead->created_at->toIso8601String(),
            'updated_at' => $lead->updated_at->toIso8601String(),
        ];

        return [
            ['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT)],
        ];
    }
}
