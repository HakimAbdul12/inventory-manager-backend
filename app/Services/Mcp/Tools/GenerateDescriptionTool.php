<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AIContentService;

class GenerateDescriptionTool implements McpTool
{
    protected AIContentService $aiService;

    public function __construct(AIContentService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function name(): string
    {
        return 'generate_description';
    }

    public function description(): string
    {
        return 'Generate an AI-powered marketing description for an inventory item based on its data (make, model, year, features, etc.). The description is saved to the item.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'The inventory item UUID to generate a description for.',
                ],
                'tone' => [
                    'type' => 'string',
                    'enum' => ['professional', 'friendly', 'luxury', 'casual'],
                    'description' => 'Tone/style of the description. Default: professional.',
                ],
                'max_length' => [
                    'type' => 'integer',
                    'description' => 'Maximum word count. Default: 200.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.ai.description';
    }

    public function category(): string
    {
        return 'AI';
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

        try {
            $description = $this->aiService->generateDescription($item);
            $data = $item->generated_data ?? [];

            // Save the description to the item
            $data['description'] = $description;
            $item->update(['generated_data' => $data]);

            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => true,
                    'id' => $item->id,
                    'description' => $description,
                    'message' => 'Description generated and saved to the inventory item.',
                ], JSON_PRETTY_PRINT)],
            ];
        } catch (\Exception $e) {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => false,
                    'error' => 'Failed to generate description: ' . $e->getMessage(),
                ])],
            ];
        }
    }
}
