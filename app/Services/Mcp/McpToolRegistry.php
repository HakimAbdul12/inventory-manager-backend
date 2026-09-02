<?php

namespace App\Services\Mcp;

use App\Contracts\McpTool;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;

/**
 * Central registry of all MCP tools.
 *
 * Discovers, registers, and resolves tools. Filters the tool list
 * based on the authenticated user's RBAC permissions within their tenant.
 */
class McpToolRegistry
{
    /** @var McpTool[] */
    protected array $tools = [];

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
        $this->registerDefaultTools();
    }

    /**
     * Register a tool instance.
     */
    public function register(McpTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Resolve a tool by name.
     */
    public function resolve(string $name): ?McpTool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all registered tools.
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Get tools that the given user has permission to use within the given tenant.
     * Returns MCP-formatted tool definitions.
     */
    public function getToolsForUser(User $user, Tenant $tenant): array
    {
        $tools = [];

        foreach ($this->tools as $tool) {
            $permission = $tool->requiredPermission();

            // If no permission required, or user has the permission
            if (!$permission || $this->permissionService->userCan($permission, $user, $tenant)) {
                $tools[] = [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'inputSchema' => $tool->inputSchema(),
                ];
            }
        }

        return $tools;
    }

    /**
     * Get all tools with their metadata (for the dashboard UI).
     * Includes permission info and category grouping.
     */
    public function getToolsWithMetadata(User $user, Tenant $tenant): array
    {
        $tools = [];

        foreach ($this->tools as $tool) {
            $permission = $tool->requiredPermission();
            $hasAccess = !$permission || $this->permissionService->userCan($permission, $user, $tenant);

            $tools[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'category' => $tool->category(),
                'required_permission' => $permission,
                'has_access' => $hasAccess,
                'input_schema' => $tool->inputSchema(),
            ];
        }

        return $tools;
    }

    /**
     * Execute a tool by name with permission checks.
     *
     * @throws \RuntimeException If tool not found or permission denied
     */
    public function executeTool(string $name, array $args, User $user, Tenant $tenant): array
    {
        $tool = $this->resolve($name);

        if (!$tool) {
            throw new \RuntimeException("Unknown tool: {$name}");
        }

        $permission = $tool->requiredPermission();
        if ($permission && !$this->permissionService->userCan($permission, $user, $tenant)) {
            throw new \RuntimeException("Permission denied: {$permission} is required to use tool '{$name}'");
        }

        Log::info("MCP tool executing: {$name}", [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'args' => $args,
        ]);

        return $tool->execute($args, $user, $tenant);
    }

    /**
     * Register all default tools.
     * Tools are organized by phase and category.
     */
    protected function registerDefaultTools(): void
    {
        // Phase 1 — Read-Only Tools
        $this->register(app(Tools\SearchInventoryTool::class));
        $this->register(app(Tools\GetInventoryItemTool::class));
        $this->register(app(Tools\ListCategoriesTool::class));
        $this->register(app(Tools\GetDashboardStatsTool::class));
        $this->register(app(Tools\ListLeadsTool::class));
        $this->register(app(Tools\GetLeadTool::class));

        // Phase 2 — Write Tools
        $this->register(app(Tools\CreateLeadTool::class));
        $this->register(app(Tools\UpdateLeadStatusTool::class));
        $this->register(app(Tools\CreateInventoryItemTool::class));
        $this->register(app(Tools\UpdateInventoryItemTool::class));
        $this->register(app(Tools\BookTestDriveTool::class));
        $this->register(app(Tools\GetTestDriveSlotsTool::class));

        // Phase 3 — AI-Powered Tools
        $this->register(app(Tools\GenerateDescriptionTool::class));
        $this->register(app(Tools\AnalyzeInventoryTool::class));
        $this->register(app(Tools\SearchKnowledgeBaseTool::class));
    }
}
