<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;

class UpdateLeadStatusTool implements McpTool
{
    public function name(): string
    {
        return 'update_lead_status';
    }

    public function description(): string
    {
        return 'Update a CRM lead\'s status (e.g., from "new" to "contacted", "engaged" to "negotiating"). Automatically records the status change in the timeline.';
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
                'status' => [
                    'type' => 'string',
                    'enum' => Lead::STATUSES,
                    'description' => 'The new status to set.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional notes about the status change.',
                ],
            ],
            'required' => ['id', 'status'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'crm.leads.edit';
    }

    public function category(): string
    {
        return 'CRM';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $lead = Lead::where('tenant_id', $tenant->id)
            ->where('id', $args['id'])
            ->first();

        if (!$lead) {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => false,
                    'error' => "Lead not found with ID: {$args['id']}",
                ])],
            ];
        }

        $oldStatus = $lead->status;
        $newStatus = $args['status'];

        $lead->update(['status' => $newStatus]);

        // Record in status history
        LeadStatusHistory::create([
            'lead_id' => $lead->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $user->id,
            'notes' => $args['notes'] ?? 'Status updated via MCP',
        ]);

        ActivityLogger::record(
            action: 'lead.status_changed',
            subject: $lead,
            description: "Lead status changed from '{$oldStatus}' to '{$newStatus}' via MCP",
            properties: [
                'from' => $oldStatus,
                'to' => $newStatus,
                'source' => 'mcp',
            ],
        );

        return [
            ['type' => 'text', 'text' => json_encode([
                'success' => true,
                'id' => $lead->id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => "Lead status updated from '{$oldStatus}' to '{$newStatus}'.",
            ], JSON_PRETTY_PRINT)],
        ];
    }
}
