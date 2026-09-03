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
        return 'Create new inventory item(s) in draft or published status. Supports creating a single vehicle or bulk creating multiple vehicles in one call via the "items" array. Can also auto-publish created vehicles directly.';
    }

    public function inputSchema(): array
    {
        $vehicleProperties = [
            'category_id' => [
                'type' => 'string',
                'description' => 'Category UUID (use list_categories to find available categories).',
            ],
            'make' => ['type' => 'string', 'description' => 'Vehicle make (e.g., Toyota, BMW, BYD).'],
            'model' => ['type' => 'string', 'description' => 'Vehicle model (e.g., Camry, X5, Sealion 7).'],
            'year' => ['type' => 'integer', 'description' => 'Model year.'],
            'price' => ['type' => 'number', 'description' => 'Asking price.'],
            'vin' => ['type' => 'string', 'description' => 'Vehicle Identification Number.'],
            'stock_number' => ['type' => 'string', 'description' => 'Dealer stock number.'],
            'mileage' => ['type' => 'integer', 'description' => 'Odometer reading.'],
            'exterior_color' => ['type' => 'string', 'description' => 'Exterior color.'],
            'interior_color' => ['type' => 'string', 'description' => 'Interior color.'],
            'body_type' => ['type' => 'string', 'description' => 'Body type (sedan, SUV, truck, coupe, etc.).'],
            'transmission' => ['type' => 'string', 'description' => 'Transmission type (automatic, manual).'],
            'fuel_type' => ['type' => 'string', 'description' => 'Fuel type (gasoline, diesel, electric, hybrid).'],
            'drivetrain' => ['type' => 'string', 'description' => 'Drivetrain (FWD, RWD, AWD, 4WD).'],
            'engine' => ['type' => 'string', 'description' => 'Engine description (e.g., 2.0L Turbo I4, Dual Motor).'],
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
        ];

        return [
            'type' => 'object',
            'properties' => array_merge([
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => $vehicleProperties,
                        'required' => ['make', 'model', 'year'],
                    ],
                    'description' => 'Array of vehicle objects for bulk creation. If provided, all vehicles in this array will be created in one operation.',
                ],
                'publish' => [
                    'type' => 'boolean',
                    'description' => 'If true, automatically initiates publishing to third-party platforms for all created vehicles in a single batch.',
                ],
                'platforms' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Platform keys to publish to if publish is true. Defaults to all active platforms.',
                ],
            ], $vehicleProperties),
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
        $rawItems = [];
        $isBulk = false;

        if (!empty($args['items']) && is_array($args['items'])) {
            $rawItems = $args['items'];
            $isBulk = true;
        } elseif (!empty($args['make']) || !empty($args['model'])) {
            $rawItems = [$args];
        } else {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'error' => 'No vehicle data provided.',
                    'message' => 'Please provide vehicle attributes (make, model, year) or an "items" array for bulk creation.',
                ], JSON_PRETTY_PRINT)],
            ];
        }

        $createdItems = [];
        $createdIds = [];
        $dataFields = [
            'make', 'model', 'year', 'price', 'vin', 'stock_number', 'mileage',
            'exterior_color', 'interior_color', 'body_type', 'transmission',
            'fuel_type', 'drivetrain', 'engine', 'description', 'features',
            'condition', 'location',
        ];

        foreach ($rawItems as $itemArgs) {
            $generatedData = [];
            foreach ($dataFields as $field) {
                if (isset($itemArgs[$field])) {
                    $generatedData[$field] = $itemArgs[$field];
                } elseif (isset($args[$field]) && !isset($itemArgs[$field])) {
                    $generatedData[$field] = $args[$field];
                }
            }

            $generatedData['title'] = trim(
                ($generatedData['year'] ?? '') . ' ' .
                ($generatedData['make'] ?? '') . ' ' .
                ($generatedData['model'] ?? '')
            );

            $status = $itemArgs['status'] ?? $args['status'] ?? InventoryItem::STATUS_DRAFT;
            $categoryId = $itemArgs['category_id'] ?? $args['category_id'] ?? \App\Models\Category::first()?->id;

            $item = InventoryItem::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'status' => $status,
                'generated_data' => $generatedData,
                'metadata' => ['created_via' => 'mcp', 'bulk' => $isBulk],
            ]);

            $hasImage = false;
            $imageInput = $itemArgs['image_path'] ?? $itemArgs['image_url'] ?? ($isBulk ? null : ($args['image_path'] ?? $args['image_url'] ?? null));

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
                properties: ['source' => 'mcp', 'tool' => 'create_inventory_item', 'has_image' => $hasImage, 'bulk' => $isBulk],
            );

            $createdItems[] = [
                'id' => $item->id,
                'title' => $generatedData['title'],
                'status' => $item->status,
                'has_image' => $hasImage,
            ];
            $createdIds[] = $item->id;
        }

        // Auto-publish if requested
        $publishBatchInfo = null;
        if (!empty($args['publish']) && !empty($createdIds)) {
            try {
                $publishTool = app(PublishInventoryItemTool::class);
                $publishResult = $publishTool->execute([
                    'inventory_ids' => $createdIds,
                    'platforms' => $args['platforms'] ?? [],
                ], $user, $tenant);

                if (!empty($publishResult[0]['text'])) {
                    $publishBatchInfo = json_decode($publishResult[0]['text'], true);
                }
            } catch (\Throwable $e) {
                $publishBatchInfo = ['error' => $e->getMessage()];
            }
        }

        if ($isBulk) {
            $response = [
                'success' => true,
                'count' => count($createdItems),
                'message' => "Successfully created " . count($createdItems) . " inventory items in bulk.",
                'items' => $createdItems,
                'inventory_ids' => $createdIds,
            ];

            if ($publishBatchInfo) {
                $response['publishing'] = $publishBatchInfo;
            }

            return [
                ['type' => 'text', 'text' => json_encode($response, JSON_PRETTY_PRINT)],
            ];
        }

        $single = $createdItems[0];
        $response = [
            'success' => true,
            'id' => $single['id'],
            'title' => $single['title'],
            'status' => $single['status'],
            'has_image' => $single['has_image'],
            'inventory_ids' => $createdIds,
            'message' => "Inventory item '{$single['title']}' created in {$single['status']} status.",
        ];

        if ($publishBatchInfo) {
            $response['publishing'] = $publishBatchInfo;
        }

        return [
            ['type' => 'text', 'text' => json_encode($response, JSON_PRETTY_PRINT)],
        ];
    }
}

