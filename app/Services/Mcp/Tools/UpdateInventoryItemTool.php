<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;

class UpdateInventoryItemTool implements McpTool
{
    public function name(): string
    {
        return 'update_inventory_item';
    }

    public function description(): string
    {
        return 'Update an existing inventory item\'s data fields, status, or category. Only provided fields are updated; others remain unchanged.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'The inventory item UUID to update.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'published', 'archived'],
                    'description' => 'Update the item status.',
                ],
                'category_id' => [
                    'type' => 'string',
                    'description' => 'Update the category.',
                ],
                'price' => ['type' => 'number', 'description' => 'Update the price.'],
                'mileage' => ['type' => 'integer', 'description' => 'Update the mileage.'],
                'description' => ['type' => 'string', 'description' => 'Update the description.'],
                'exterior_color' => ['type' => 'string', 'description' => 'Update exterior color.'],
                'interior_color' => ['type' => 'string', 'description' => 'Update interior color.'],
                'features' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Replace the features list.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.edit';
    }

    public function category(): string
    {
        return 'Inventory';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $item = InventoryItem::where('tenant_id', $tenant->id)
            ->where('id', $args['id'])
            ->first();

        if (!$item) {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => false,
                    'error' => "Inventory item not found with ID: {$args['id']}",
                ])],
            ];
        }

        $oldData = $item->generated_data ?? [];
        $updatedData = $oldData;
        $changes = [];

        // Update generated_data fields
        $dataFields = [
            'price', 'mileage', 'description', 'exterior_color',
            'interior_color', 'features',
        ];
        foreach ($dataFields as $field) {
            if (array_key_exists($field, $args)) {
                $oldValue = $updatedData[$field] ?? null;
                $updatedData[$field] = $args[$field];
                if ($oldValue !== $args[$field]) {
                    $changes[$field] = ['old' => $oldValue, 'new' => $args[$field]];
                }
            }
        }

        $modelUpdates = ['generated_data' => $updatedData];

        // Update status
        if (isset($args['status']) && $args['status'] !== $item->status) {
            $changes['status'] = ['old' => $item->status, 'new' => $args['status']];
            $modelUpdates['status'] = $args['status'];
        }

        // Update category
        if (isset($args['category_id']) && $args['category_id'] !== $item->category_id) {
            $changes['category_id'] = ['old' => $item->category_id, 'new' => $args['category_id']];
            $modelUpdates['category_id'] = $args['category_id'];
        }

        $item->update($modelUpdates);

        if (!empty($changes)) {
            ActivityLogger::record(
                action: 'inventory.updated',
                subject: $item,
                description: "Inventory item updated via MCP (" . count($changes) . " field(s) changed)",
                properties: ['changes' => $changes, 'source' => 'mcp'],
            );
        }

        return [
            ['type' => 'text', 'text' => json_encode([
                'success' => true,
                'id' => $item->id,
                'fields_updated' => array_keys($changes),
                'message' => empty($changes)
                    ? 'No changes detected.'
                    : count($changes) . " field(s) updated successfully.",
            ], JSON_PRETTY_PRINT)],
        ];
    }
}
