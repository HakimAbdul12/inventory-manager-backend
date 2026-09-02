<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\InventoryItem;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;

class GetDashboardStatsTool implements McpTool
{
    public function name(): string
    {
        return 'get_dashboard_stats';
    }

    public function description(): string
    {
        return 'Get workspace dashboard statistics including inventory counts by status, lead counts, recent activity summary, and key metrics.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.view';
    }

    public function category(): string
    {
        return 'Dashboard';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        // Inventory counts by status
        $inventoryCounts = InventoryItem::where('tenant_id', $tenant->id)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalInventory = array_sum($inventoryCounts);

        // Lead counts by status
        $leadCounts = Lead::where('tenant_id', $tenant->id)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalLeads = array_sum($leadCounts);

        // Recent items added (last 7 days)
        $recentItems = InventoryItem::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Recent leads (last 7 days)
        $recentLeads = Lead::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $stats = [
            'inventory' => [
                'total' => $totalInventory,
                'by_status' => $inventoryCounts,
                'added_last_7_days' => $recentItems,
            ],
            'leads' => [
                'total' => $totalLeads,
                'by_status' => $leadCounts,
                'added_last_7_days' => $recentLeads,
            ],
            'workspace' => [
                'name' => $tenant->name,
                'member_count' => $tenant->getMemberCount(),
            ],
        ];

        return [
            ['type' => 'text', 'text' => json_encode($stats, JSON_PRETTY_PRINT)],
        ];
    }
}
