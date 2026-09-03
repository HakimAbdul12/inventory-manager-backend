<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Jobs\ProcessPublishingItemJob;
use App\Models\InventoryItem;
use App\Models\InventoryPublishingStatus;
use App\Models\PublishingBatch;
use App\Models\PublishingBatchItem;
use App\Models\PublishingPlatform;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishInventoryItemTool implements McpTool
{
    public function name(): string
    {
        return 'publish_inventory_item';
    }

    public function description(): string
    {
        return 'Publish a vehicle inventory listing to third-party marketplaces (e.g., OnlyEV, CarGurus, Facebook, AutoTech). You can specify a vehicle UUID or a search query (e.g., "Cybertruck", "2024 Porsche Taycan", "Taycan added yesterday"). Targets specific platforms or all active platforms.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'The vehicle inventory UUID to publish (optional if query is provided).',
                ],
                'query' => [
                    'type' => 'string',
                    'description' => 'Search term or vehicle model/make/keywords to locate the vehicle (e.g., "Cybertruck", "Porsche Taycan", "Civic").',
                ],
                'platforms' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of platform keys to publish to (e.g., ["onlyev", "cargurus", "facebook", "autotech"]). Defaults to all active platforms.',
                ],
                'format' => [
                    'type' => 'string',
                    'enum' => ['image', 'video'],
                    'description' => 'Media format to use (default: "image").',
                ],
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.publish';
    }

    public function category(): string
    {
        return 'Publishing';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $vehicle = null;

        // 1. Find vehicle by ID or search query
        if (!empty($args['id'])) {
            $vehicle = InventoryItem::where('tenant_id', $tenant->id)
                ->where('id', $args['id'])
                ->first();
        }

        if (!$vehicle && !empty($args['query'])) {
            $queryStr = trim($args['query']);
            $isPgsql = DB::getDriverName() === 'pgsql';

            $vehicleQuery = InventoryItem::where('tenant_id', $tenant->id);

            $vehicleQuery->where(function ($q) use ($queryStr, $isPgsql) {
                if ($isPgsql) {
                    $q->whereRaw("generated_data::text ILIKE ?", ["%{$queryStr}%"])
                      ->orWhereRaw("metadata::text ILIKE ?", ["%{$queryStr}%"]);
                } else {
                    $q->where('generated_data', 'LIKE', "%{$queryStr}%")
                      ->orWhere('metadata', 'LIKE', "%{$queryStr}%");
                }
            });

            $vehicle = $vehicleQuery->orderBy('created_at', 'desc')->first();
        }

        if (!$vehicle) {
            return [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'error' => 'Vehicle not found.',
                        'message' => 'No matching inventory item found in this workspace. Please provide a valid vehicle ID or refine your search query.',
                        'query' => $args['query'] ?? null,
                    ], JSON_PRETTY_PRINT),
                ],
            ];
        }

        $vehicleData = $vehicle->generated_data ?? [];
        $vehicleTitle = $vehicleData['title'] ?? 'Vehicle #' . substr($vehicle->id, 0, 8);

        // 2. Resolve active platforms
        $activePlatforms = PublishingPlatform::active()->get();
        if ($activePlatforms->isEmpty()) {
            return [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'error' => 'No active publishing platforms available in this workspace.',
                    ], JSON_PRETTY_PRINT),
                ],
            ];
        }

        $requestedPlatforms = $args['platforms'] ?? [];
        $targetPlatforms = [];

        if (!empty($requestedPlatforms)) {
            foreach ($requestedPlatforms as $reqPlat) {
                $norm = strtolower(trim(str_replace([' ', '-', '_'], '', $reqPlat)));
                $matched = $activePlatforms->first(function ($p) use ($norm) {
                    $pNorm = strtolower(str_replace([' ', '-', '_'], '', $p->key));
                    $nameNorm = strtolower(str_replace([' ', '-', '_'], '', $p->name));
                    return str_contains($pNorm, $norm) || str_contains($nameNorm, $norm) || str_contains($norm, $pNorm);
                });

                if ($matched && !in_array($matched->key, array_column($targetPlatforms, 'id'))) {
                    $targetPlatforms[] = [
                        'id' => $matched->key,
                        'name' => $matched->name,
                        'format' => $args['format'] ?? 'image',
                    ];
                }
            }
        }

        // Fallback: If no platforms matched or none specified, use all active platforms
        if (empty($targetPlatforms)) {
            $targetPlatforms = $activePlatforms->map(fn($p) => [
                'id' => $p->key,
                'name' => $p->name,
                'format' => $args['format'] ?? 'image',
            ])->toArray();
        }

        // 3. Mark vehicle as published
        $vehicle->update(['status' => InventoryItem::STATUS_PUBLISHED]);

        // 4. Create Batch & Items
        $batch = DB::transaction(function () use ($tenant, $user, $vehicle, $targetPlatforms, $vehicleData) {
            $batch = PublishingBatch::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'status' => 'in_progress',
                'total_items' => count($targetPlatforms),
                'successful_items' => 0,
                'failed_items' => 0,
                'metadata' => [
                    'vehicles_count' => 1,
                    'platforms_count' => count($targetPlatforms),
                    'platforms' => array_column($targetPlatforms, 'id'),
                    'source' => 'mcp',
                ],
            ]);

            $firstItemToDispatch = null;

            foreach ($targetPlatforms as $platform) {
                $platformKey = $platform['id'];

                // Deduplication: check if already published
                $alreadyPublished = InventoryPublishingStatus::where('tenant_id', $tenant->id)
                    ->where('inventory_item_id', $vehicle->id)
                    ->where('platform_name', $platformKey)
                    ->where('status', 'success')
                    ->exists();

                if (!$alreadyPublished) {
                    $alreadyPublished = PublishingBatchItem::where('inventory_item_id', $vehicle->id)
                        ->where('platform_key', $platformKey)
                        ->where('status', 'published')
                        ->exists();
                }

                if ($alreadyPublished) {
                    PublishingBatchItem::create([
                        'batch_id' => $batch->id,
                        'inventory_item_id' => $vehicle->id,
                        'platform_key' => $platformKey,
                        'format' => $platform['format'] ?? 'image',
                        'status' => 'published',
                        'attempts' => 1,
                        'max_attempts' => 3,
                        'error_message' => 'Already published. Existing publication reused.',
                        'last_attempted_at' => now(),
                    ]);
                    continue;
                }

                // Platform rule: OnlyEV
                if ($platformKey === 'onlyev') {
                    $fuelType = strtolower($vehicleData['fuel_type'] ?? $vehicleData['engine'] ?? '');
                    $isEvOrHybrid = str_contains($fuelType, 'electric') ||
                                    str_contains($fuelType, 'hybrid') ||
                                    str_contains($fuelType, 'phev') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'taycan') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'tesla') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'cybertruck') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'ioniq') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'leaf') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'mach-e') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'rivian') ||
                                    str_contains(strtolower($vehicleData['model'] ?? ''), 'lucid');

                    if (!$isEvOrHybrid) {
                        $currentFuel = !empty($vehicleData['fuel_type']) ? ucfirst($vehicleData['fuel_type']) : 'Gasoline';
                        PublishingBatchItem::create([
                            'batch_id' => $batch->id,
                            'inventory_item_id' => $vehicle->id,
                            'platform_key' => $platformKey,
                            'format' => $platform['format'] ?? 'image',
                            'status' => 'skipped',
                            'attempts' => 0,
                            'max_attempts' => 3,
                            'error_message' => "OnlyEV requires Electric or Hybrid vehicles (current: {$currentFuel}).",
                            'last_attempted_at' => now(),
                        ]);
                        continue;
                    }
                }

                // Normal item
                $isFirstPending = ($firstItemToDispatch === null);
                $batchItem = PublishingBatchItem::create([
                    'batch_id' => $batch->id,
                    'inventory_item_id' => $vehicle->id,
                    'platform_key' => $platformKey,
                    'format' => $platform['format'] ?? 'image',
                    'status' => $isFirstPending ? 'in_progress' : 'pending',
                    'attempts' => $isFirstPending ? 1 : 0,
                    'max_attempts' => 3,
                    'last_attempted_at' => $isFirstPending ? now() : null,
                ]);

                if ($isFirstPending) {
                    $firstItemToDispatch = $batchItem;
                }
            }

            if ($firstItemToDispatch) {
                ProcessPublishingItemJob::dispatch($firstItemToDispatch->id);
            }

            $batch->updateProgress();
            return $batch;
        });

        // Broadcast initial status update
        $firstItem = $batch->items()->first();
        if ($firstItem) {
            try {
                event(new \App\Events\PublishingItemStatusUpdated(
                    $firstItem,
                    ['message' => "Publishing {$vehicleTitle} via AI Assistant."]
                ));
            } catch (\Throwable $e) {
                // Ignore broadcast error
            }
        }

        $itemsSummary = $batch->items()->get()->map(function ($item) {
            return [
                'platform' => $item->platform_key,
                'status' => $item->status,
                'note' => $item->error_message,
            ];
        });

        return [
            [
                'type' => 'text',
                'text' => json_encode([
                    'success' => true,
                    'message' => "Publishing batch initiated for '{$vehicleTitle}'.",
                    'vehicle' => [
                        'id' => $vehicle->id,
                        'title' => $vehicleTitle,
                        'year' => $vehicleData['year'] ?? null,
                        'make' => $vehicleData['make'] ?? null,
                        'model' => $vehicleData['model'] ?? null,
                        'price' => $vehicleData['price'] ?? null,
                        'status' => $vehicle->status,
                    ],
                    'batch' => [
                        'id' => $batch->id,
                        'status' => $batch->status,
                        'total_platforms' => count($targetPlatforms),
                        'platforms_summary' => $itemsSummary,
                        'history_url' => "/dashboard/publishing/history/{$batch->id}",
                    ],
                ], JSON_PRETTY_PRINT),
            ],
        ];
    }
}
