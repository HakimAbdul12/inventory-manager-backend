<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;

class SearchInventoryTool implements McpTool
{
    public function name(): string
    {
        return 'search_inventory';
    }

    public function description(): string
    {
        return 'Search the dealership vehicle inventory by keyword, make, model, year range, price range, body type, or status. Returns matching vehicles with images and pricing.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Natural language search query (e.g., "BMW X5", "SUV under 50000", "family car with AWD").',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'published', 'archived'],
                    'description' => 'Filter by item status. Defaults to all statuses.',
                ],
                'min_price' => [
                    'type' => 'number',
                    'description' => 'Minimum price filter.',
                ],
                'max_price' => [
                    'type' => 'number',
                    'description' => 'Maximum price filter.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return. Default 10, max 50.',
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => 'Page number for pagination. Default 1.',
                ],
            ],
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
        $query = $args['query'] ?? '';
        $status = $args['status'] ?? null;
        $minPrice = $args['min_price'] ?? null;
        $maxPrice = $args['max_price'] ?? null;
        $limit = min($args['limit'] ?? 10, 50);
        $page = max($args['page'] ?? 1, 1);

        $builder = InventoryItem::where('tenant_id', $tenant->id)
            ->with(['images' => fn($q) => $q->orderBy('is_primary', 'desc')->limit(3)]);

        if ($status) {
            $builder->where('status', $status);
        }

        $isPgsql = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql';

        // Search across generated_data JSON fields
        if ($query) {
            $builder->where(function ($q) use ($query, $isPgsql) {
                if ($isPgsql) {
                    $q->whereRaw("generated_data::text ILIKE ?", ["%{$query}%"])
                      ->orWhereRaw("metadata::text ILIKE ?", ["%{$query}%"]);
                } else {
                    $q->whereRaw("generated_data LIKE ?", ["%{$query}%"])
                      ->orWhereRaw("metadata LIKE ?", ["%{$query}%"]);
                }
            });
        }

        // Price filtering — prices are stored in generated_data JSON
        if ($minPrice !== null) {
            if ($isPgsql) {
                $builder->whereRaw("CAST(generated_data->>'price' AS NUMERIC) >= ?", [$minPrice]);
            } else {
                $builder->whereRaw("CAST(json_extract(generated_data, '$.price') AS NUMERIC) >= ?", [$minPrice]);
            }
        }
        if ($maxPrice !== null) {
            if ($isPgsql) {
                $builder->whereRaw("CAST(generated_data->>'price' AS NUMERIC) <= ?", [$maxPrice]);
            } else {
                $builder->whereRaw("CAST(json_extract(generated_data, '$.price') AS NUMERIC) <= ?", [$maxPrice]);
            }
        }

        $total = $builder->count();
        $items = $builder->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $vehicles = $items->map(function ($item) {
            $data = $item->generated_data ?? [];
            return [
                'id' => $item->id,
                'status' => $item->status,
                'title' => $data['title'] ?? ($data['year'] ?? '') . ' ' . ($data['make'] ?? '') . ' ' . ($data['model'] ?? ''),
                'make' => $data['make'] ?? null,
                'model' => $data['model'] ?? null,
                'year' => $data['year'] ?? null,
                'price' => $data['price'] ?? null,
                'mileage' => $data['mileage'] ?? null,
                'body_type' => $data['body_type'] ?? null,
                'color' => $data['exterior_color'] ?? $data['color'] ?? null,
                'vin' => $data['vin'] ?? null,
                'stock_number' => $data['stock_number'] ?? null,
                'description' => isset($data['description']) ? substr($data['description'], 0, 200) . '...' : null,
                'image_count' => $item->images->count(),
                'primary_image' => $item->images->first()?->url,
                'created_at' => $item->created_at->toIso8601String(),
            ];
        })->toArray();

        $text = empty($vehicles)
            ? "No vehicles found matching your search criteria."
            : "Found {$total} vehicle(s). Showing page {$page} ({$limit} per page):\n\n" . json_encode($vehicles, JSON_PRETTY_PRINT);

        return [
            ['type' => 'text', 'text' => $text],
        ];
    }
}
