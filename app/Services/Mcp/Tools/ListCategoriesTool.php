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
        return 'List all available product categories (e.g., Cars, Phones).';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'active_only' => [
                    'type' => 'boolean',
                    'description' => 'Optional filter to return only active categories. Default: false.',
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
        $query = Category::query();

        if (!empty($args['active_only'])) {
            $query->active();
        }

        $categories = $query->orderBy('name')->get(['id', 'name', 'slug', 'description', 'icon', 'is_active', 'sort_order'])->toArray();

        $text = empty($categories)
            ? "No categories found."
            : json_encode($categories, JSON_PRETTY_PRINT);

        return [
            ['type' => 'text', 'text' => $text],
        ];
    }
}

