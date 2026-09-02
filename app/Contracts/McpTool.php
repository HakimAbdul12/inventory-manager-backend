<?php

namespace App\Contracts;

use App\Models\Tenant;
use App\Models\User;

/**
 * Contract for MCP tools.
 *
 * Each tool represents a discrete action that an external AI client can invoke
 * through the Model Context Protocol. Tools are permission-gated and tenant-scoped.
 */
interface McpTool
{
    /**
     * Unique tool name (snake_case, e.g. "search_inventory").
     */
    public function name(): string;

    /**
     * Human-readable description for AI clients to understand what this tool does.
     */
    public function description(): string;

    /**
     * JSON Schema describing the tool's input parameters.
     * Must follow the MCP inputSchema format (type: "object", properties, required).
     */
    public function inputSchema(): array;

    /**
     * The permission key required to use this tool, or null if no permission is needed.
     * Maps to the existing tenant RBAC system (e.g. "inventory.view", "crm.leads.create").
     */
    public function requiredPermission(): ?string;

    /**
     * Execute the tool with the given arguments in the context of a user and tenant.
     *
     * @param  array  $args   Validated input arguments
     * @param  User   $user   The authenticated user making the request
     * @param  Tenant $tenant The active tenant/workspace
     * @return array  MCP content array (e.g. [['type' => 'text', 'text' => '...']])
     */
    public function execute(array $args, User $user, Tenant $tenant): array;

    /**
     * Optional: category for grouping tools in the dashboard UI.
     */
    public function category(): string;
}
