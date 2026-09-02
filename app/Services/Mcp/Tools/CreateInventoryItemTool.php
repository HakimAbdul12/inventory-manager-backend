<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;

class CreateInventoryItemTool implements McpTool
{
    public function name(): string
    {
        return 'create_inventory_item';
    }

    public function description(): string
    {
        return 'Create a new inventory item in draft status. Provide vehicle details like make, model, year, price, VIN, color, mileage, and description.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'category_id' => [
                    'type' => 'string',
                    'description' => 'Category UUID (use list_categories to find available categories).',
                ],
                'make' => ['type' => 'string', 'description' => 'Vehicle make (e.g., Toyota, BMW).'],
                'model' => ['type' => 'string', 'description' => 'Vehicle model (e.g., Camry, X5).'],
                'year' => ['type' => 'integer', 'description' => 'Model year.'],
                'price' => ['type' => 'number', 'description' => 'Asking price.'],
                'vin' => ['type' => 'string', 'description' => 'Vehicle Identification Number.'],
                'stock_number' => ['type' => 'string', 'description' => 'Dealer stock number.'],
                'mileage' => ['type' => 'integer', 'description' => 'Odometer reading.'],
                'exterior_color' => ['type' => 'string', 'description' => 'Exterior color.'],
                'interior_color' => ['type' => 'string', 'description' => 'Interior color.'],
                'body_type' => ['type' => 'string', 'description' => 'Body type (sedan, SUV, truck, etc.).'],
                'transmission' => ['type' => 'string', 'description' => 'Transmission type (automatic, manual).'],
                'fuel_type' => ['type' => 'string', 'description' => 'Fuel type (gasoline, diesel, electric, hybrid).'],
                'drivetrain' => ['type' => 'string', 'description' => 'Drivetrain (FWD, RWD, AWD, 4WD).'],
                'engine' => ['type' => 'string', 'description' => 'Engine description (e.g., 2.0L Turbo I4).'],
                'description' => ['type' => 'string', 'description' => 'Marketing description text.'],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'published', 'archived'],
                    'description' => 'Vehicle status (defaults to draft).',
                ],
                'image_path' => [
                    'type' => 'string',
                    'description' => 'Local file path to vehicle image (will be imported and set as primary).',
                ],
                'image_url' => [
                    'type' => 'string',
                    'description' => 'Public URL or relative path to vehicle image.',
                ],
                'features' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of features (e.g., ["Sunroof", "Heated Seats", "Navigation"]).',
                ],
            ],
            'required' => ['make', 'model', 'year'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.create';
    }

    public function category(): string
    {
        return 'Inventory';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $generatedData = [];
        $dataFields = [
            'make', 'model', 'year', 'price', 'vin', 'stock_number', 'mileage',
            'exterior_color', 'interior_color', 'body_type', 'transmission',
            'fuel_type', 'drivetrain', 'engine', 'description', 'features',
            'condition', 'location',
        ];

        foreach ($dataFields as $field) {
            if (isset($args[$field])) {
                $generatedData[$field] = $args[$field];
            }
        }

        // Build a title from the data
        $generatedData['title'] = trim(
            ($args['year'] ?? '') . ' ' .
            ($args['make'] ?? '') . ' ' .
            ($args['model'] ?? '')
        );

        $status = $args['status'] ?? InventoryItem::STATUS_DRAFT;
        $categoryId = $args['category_id'] ?? \App\Models\Category::first()?->id;

        $item = InventoryItem::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'category_id' => $categoryId,
            'status' => $status,
            'generated_data' => $generatedData,
            'metadata' => ['created_via' => 'mcp'],
        ]);

        $hasImage = false;
        $imageInput = $args['image_path'] ?? $args['image_url'] ?? null;

        if ($imageInput) {
            $destDir = storage_path("app/public/inventory/{$item->id}");
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $ext = pathinfo(parse_url($imageInput, PHP_URL_PATH) ?? 'image.jpg', PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'image_' . substr(md5(uniqid()), 0, 10) . '.' . $ext;
            $destPath = "{$destDir}/{$filename}";

            $copied = false;
            if (file_exists($imageInput)) {
                $copied = copy($imageInput, $destPath);
            } elseif (filter_var($imageInput, FILTER_VALIDATE_URL)) {
                $contents = @file_get_contents($imageInput);
                if ($contents !== false) {
                    $copied = (file_put_contents($destPath, $contents) !== false);
                }
            }

            if ($copied) {
                $relativeUrl = "/storage/inventory/{$item->id}/{$filename}";
                $sizes = [
                    'original' => $relativeUrl,
                    'large' => $relativeUrl,
                    'medium' => $relativeUrl,
                    'thumbnail' => $relativeUrl,
                ];

                $item->images()->create([
                    'path' => "inventory/{$item->id}/{$filename}",
                    'alt' => $generatedData['title'],
                    'is_primary' => true,
                    'is_approved' => true,
                    'processing_status' => \App\Models\InventoryImage::STATUS_COMPLETED,
                    'sizes' => $sizes,
                ]);
                $hasImage = true;
            }
        }

        ActivityLogger::record(
            action: 'inventory.created',
            subject: $item,
            description: "Inventory item '{$generatedData['title']}' created via MCP",
            properties: ['source' => 'mcp', 'tool' => 'create_inventory_item', 'has_image' => $hasImage],
        );

        return [
            ['type' => 'text', 'text' => json_encode([
                'success' => true,
                'id' => $item->id,
                'title' => $generatedData['title'],
                'status' => $item->status,
                'has_image' => $hasImage,
                'message' => "Inventory item '{$generatedData['title']}' created in {$item->status} status.",
            ], JSON_PRETTY_PRINT)],
        ];
    }
}
