<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;

class ListCategoriesTool implements McpTool
{
    public function name(): string
    {
        return 'list_categories';
    }

    public function description(): string
    {
        return 'List all available product categories (e.g., Cars, Trucks, SUVs). Categories are hierarchical with parent-child relationships.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'parent_id' => [
                    'type' => 'string',
                    'description' => 'Optional parent category ID to list only children of a specific category.',
                ],
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return null; // Categories are public data
    }

    public function category(): string
    {
        return 'Inventory';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $query = Category::orderBy('name');

        if (isset($args['parent_id'])) {
            $query->where('parent_id', $args['parent_id']);
        }

        $categories = $query->get(['id', 'name', 'slug', 'parent_id', 'description'])->toArray();

        $text = empty($categories)
            ? "No categories found."
            : json_encode($categories, JSON_PRETTY_PRINT);

        return [
            ['type' => 'text', 'text' => $text],
        ];
    }
}
