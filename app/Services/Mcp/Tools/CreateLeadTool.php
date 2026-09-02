<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;

class CreateLeadTool implements McpTool
{
    public function name(): string
    {
        return 'create_lead';
    }

    public function description(): string
    {
        return 'Create a new CRM lead with contact information. The lead will be created with "new" status and can be assigned to a team member.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'first_name' => [
                    'type' => 'string',
                    'description' => 'Lead first name.',
                ],
                'last_name' => [
                    'type' => 'string',
                    'description' => 'Lead last name.',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Lead email address.',
                ],
                'phone' => [
                    'type' => 'string',
                    'description' => 'Lead phone number.',
                ],
                'source_type' => [
                    'type' => 'string',
                    'enum' => Lead::SOURCE_TYPES,
                    'description' => 'How the lead was acquired. Default: "manual".',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Free-text notes about the lead.',
                ],
                'assigned_to' => [
                    'type' => 'string',
                    'description' => 'User ID to assign the lead to.',
                ],
            ],
            'required' => ['first_name'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'crm.leads.create';
    }

    public function category(): string
    {
        return 'CRM';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'first_name' => $args['first_name'],
            'last_name' => $args['last_name'] ?? null,
            'email' => $args['email'] ?? null,
            'phone' => $args['phone'] ?? null,
            'source_type' => $args['source_type'] ?? Lead::SOURCE_MANUAL,
            'status' => Lead::STATUS_NEW,
            'lifecycle_stage' => Lead::LIFECYCLE_NEW,
            'notes' => $args['notes'] ?? null,
            'assigned_to' => $args['assigned_to'] ?? null,
            'created_by' => $user->id,
        ]);

        ActivityLogger::record(
            action: 'lead.created',
            subject: $lead,
            description: "Lead '{$lead->first_name} {$lead->last_name}' created via MCP",
            properties: ['source' => 'mcp', 'tool' => 'create_lead'],
        );

        return [
            ['type' => 'text', 'text' => json_encode([
                'success' => true,
                'id' => $lead->id,
                'message' => "Lead '{$lead->first_name} {$lead->last_name}' created successfully.",
            ], JSON_PRETTY_PRINT)],
        ];
    }
}
