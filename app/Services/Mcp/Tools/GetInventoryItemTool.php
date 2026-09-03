<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;

class GetInventoryItemTool implements McpTool
{
    public function name(): string
    {
        return 'get_inventory_item';
    }

    public function description(): string
    {
        return 'Fetch a single inventory item by its ID with full details including images, documents, videos, pricing history, and all generated data fields.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'The inventory item UUID.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.view';
    }

    public function category(): string
    {
        return 'Inventory';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $item = InventoryItem::where('tenant_id', $tenant->id)
            ->where('id', $args['id'])
            ->with(['images', 'videos', 'documents', 'category', 'priceHistory'])
            ->first();

        if (!$item) {
            return [
                ['type' => 'text', 'text' => "Inventory item not found with ID: {$args['id']}"],
            ];
        }

        $data = $item->generated_data ?? [];

        $result = [
            'id' => $item->id,
            'status' => $item->status,
            'category' => $item->category?->name,
            'confidence_score' => $item->confidence_score,
            'generated_data' => $data,
            'metadata' => $item->metadata,
            'analysis_results' => $item->analysis_results,
            'images' => $item->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'is_primary' => $img->is_primary,
                'type' => $img->type ?? 'photo',
            ])->toArray(),
            'videos' => $item->videos->map(fn($v) => [
                'id' => $v->id,
                'url' => $v->url,
            ])->toArray(),
            'documents' => $item->documents->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'url' => $d->url,
            ])->toArray(),
            'price_history' => $item->priceHistory->map(fn($ph) => [
                'old_price' => $ph->old_price,
                'new_price' => $ph->new_price,
                'source' => $ph->source,
                'notes' => $ph->notes,
                'changed_at' => $ph->created_at?->toIso8601String(),
            ])->toArray(),
            'created_at' => $item->created_at->toIso8601String(),
            'updated_at' => $item->updated_at->toIso8601String(),
        ];

        return [
            ['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT)],
        ];
    }
}
