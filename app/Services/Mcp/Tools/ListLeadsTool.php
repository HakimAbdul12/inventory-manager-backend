<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;

class ListLeadsTool implements McpTool
{
    public function name(): string
    {
        return 'list_leads';
    }

    public function description(): string
    {
        return 'List CRM leads for the workspace. Filter by status, source, assigned user, or date range. Returns contact info, status, and associated vehicles.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => Lead::STATUSES,
                    'description' => 'Filter by lead status.',
                ],
                'source' => [
                    'type' => 'string',
                    'enum' => Lead::SOURCE_TYPES,
                    'description' => 'Filter by lead source (e.g., website, chat, walk_in).',
                ],
                'assigned_to' => [
                    'type' => 'string',
                    'description' => 'Filter by assigned user ID.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search by name, email, or phone.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum results per page. Default 20, max 100.',
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => 'Page number. Default 1.',
                ],
            ],
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
        $limit = min($args['limit'] ?? 20, 100);
        $page = max($args['page'] ?? 1, 1);

        $query = Lead::where('tenant_id', $tenant->id)
            ->with(['assignedUser:id,name']);

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (isset($args['source'])) {
            $query->where('source_type', $args['source']);
        }
        if (isset($args['assigned_to'])) {
            $query->where('assigned_to', $args['assigned_to']);
        }
        if (isset($args['search'])) {
            $search = $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('phone', 'ILIKE', "%{$search}%");
            });
        }

        $total = $query->count();
        $leads = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $result = $leads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'name' => trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')),
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => $lead->status,
                'source_type' => $lead->source_type,
                'lifecycle_stage' => $lead->lifecycle_stage,
                'assigned_to' => $lead->assignedUser?->name,
                'notes' => $lead->notes ? substr($lead->notes, 0, 150) : null,
                'created_at' => $lead->created_at->toIso8601String(),
            ];
        })->toArray();

        $text = empty($result)
            ? "No leads found matching the criteria."
            : "Found {$total} lead(s). Showing page {$page}:\n\n" . json_encode($result, JSON_PRETTY_PRINT);

        return [
            ['type' => 'text', 'text' => $text],
        ];
    }
}
