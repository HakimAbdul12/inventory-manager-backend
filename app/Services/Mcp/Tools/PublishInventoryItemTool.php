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
        return 'Publish one or multiple vehicle inventory listings to third-party marketplaces (e.g., OnlyEV, CarGurus, Facebook, AutoTech) in a single unified batch. You can specify multiple vehicle UUIDs via inventory_ids or ids, a single vehicle UUID via id, a search query, or all_drafts. Targets specific platforms or all active platforms in one batch.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'inventory_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Array of vehicle inventory UUIDs to publish together in a single batch.',
                ],
                'ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Alias for inventory_ids. Array of vehicle UUIDs.',
                ],
                'id' => [
                    'type' => 'string',
                    'description' => 'The vehicle inventory UUID to publish (or comma-separated UUIDs).',
                ],
                'query' => [
                    'type' => 'string',
                    'description' => 'Search term or vehicle model/make/keywords to locate the vehicle(s) (e.g., "Cybertruck", "Porsche Taycan", "Civic").',
                ],
                'all_drafts' => [
                    'type' => 'boolean',
                    'description' => 'If true, publishes all vehicles currently in draft status in this workspace.',
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
        $targetIds = [];

        if (!empty($args['inventory_ids']) && is_array($args['inventory_ids'])) {
            $targetIds = array_merge($targetIds, $args['inventory_ids']);
        }
        if (!empty($args['ids']) && is_array($args['ids'])) {
            $targetIds = array_merge($targetIds, $args['ids']);
        }

        if (!empty($args['id'])) {
            if (is_array($args['id'])) {
                $targetIds = array_merge($targetIds, $args['id']);
            } elseif (is_string($args['id'])) {
                if (str_contains($args['id'], ',')) {
                    $targetIds = array_merge($targetIds, array_map('trim', explode(',', $args['id'])));
                } else {
                    $targetIds[] = trim($args['id']);
                }
            }
        }

        $targetIds = array_values(array_unique(array_filter($targetIds)));

        $vehiclesQuery = InventoryItem::where('tenant_id', $tenant->id);

        if (!empty($targetIds)) {
            $vehiclesQuery->whereIn('id', $targetIds);
        } elseif (!empty($args['all_drafts'])) {
            $vehiclesQuery->where('status', InventoryItem::STATUS_DRAFT);
        } elseif (!empty($args['query'])) {
            $queryStr = trim($args['query']);
            $isPgsql = DB::getDriverName() === 'pgsql';

            $vehiclesQuery->where(function ($q) use ($queryStr, $isPgsql) {
                if ($isPgsql) {
                    $q->whereRaw("generated_data::text ILIKE ?", ["%{$queryStr}%"])
                      ->orWhereRaw("metadata::text ILIKE ?", ["%{$queryStr}%"]);
                } else {
                    $q->where('generated_data', 'LIKE', "%{$queryStr}%")
                      ->orWhere('metadata', 'LIKE', "%{$queryStr}%");
                }
            });
        } else {
            return [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'error' => 'No vehicles specified.',
                        'message' => 'Please provide inventory_ids, id, query, or set all_drafts to true.',
                    ], JSON_PRETTY_PRINT),
                ],
            ];
        }

        $vehicles = $vehiclesQuery->orderBy('created_at', 'desc')->get();

        if ($vehicles->isEmpty()) {
            return [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'error' => 'Vehicle(s) not found.',
                        'message' => 'No matching inventory item(s) found in this workspace.',
                        'query' => $args['query'] ?? null,
                        'ids' => $targetIds,
                    ], JSON_PRETTY_PRINT),
                ],
            ];
        }

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

        // 3. Mark all selected vehicles as published
        InventoryItem::where('tenant_id', $tenant->id)
            ->whereIn('id', $vehicles->pluck('id'))
            ->update(['status' => InventoryItem::STATUS_PUBLISHED]);

        $totalTasks = count($vehicles) * count($targetPlatforms);

        // 4. Create ONE unified PublishingBatch for ALL vehicles
        $batch = DB::transaction(function () use ($tenant, $user, $vehicles, $targetPlatforms, $totalTasks) {
            $batch = PublishingBatch::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'status' => 'in_progress',
                'total_items' => $totalTasks,
                'successful_items' => 0,
                'failed_items' => 0,
                'metadata' => [
                    'vehicles_count' => count($vehicles),
                    'platforms_count' => count($targetPlatforms),
                    'platforms' => array_column($targetPlatforms, 'id'),
                    'source' => 'mcp',
                ],
            ]);

            foreach ($vehicles as $vehicle) {
                $firstItemToDispatch = null;
                $vehicleData = $vehicle->generated_data ?? [];

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

                    // Platform rule: OnlyEV requires EV/Hybrid
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

                    // Normal queueable item
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
            }

            $batch->updateProgress();
            return $batch;
        });

        // Broadcast initial status update
        $firstItem = $batch->items()->first();
        if ($firstItem) {
            try {
                $vCount = count($vehicles);
                event(new \App\Events\PublishingItemStatusUpdated(
                    $firstItem,
                    ['message' => "Publishing {$vCount} vehicle(s) in a single unified batch via AI Assistant."]
                ));
            } catch (\Throwable $e) {
                // Ignore broadcast error
            }
        }

        $vehiclesSummary = $vehicles->map(fn($v) => [
            'id' => $v->id,
            'title' => $v->generated_data['title'] ?? 'Vehicle #' . substr($v->id, 0, 8),
            'status' => $v->status,
        ])->values()->toArray();

        $vNames = $vehicles->map(fn($v) => $v->generated_data['title'] ?? 'Vehicle #' . substr($v->id, 0, 8))->implode(', ');

        return [
            [
                'type' => 'text',
                'text' => json_encode([
                    'success' => true,
                    'message' => "Publishing batch initiated for " . count($vehicles) . " vehicle(s) ({$vNames}) across " . count($targetPlatforms) . " platform(s).",
                    'batch' => [
                        'id' => $batch->id,
                        'status' => $batch->status,
                        'vehicles_count' => count($vehicles),
                        'total_tasks' => $batch->total_items,
                        'platforms_count' => count($targetPlatforms),
                        'vehicles' => $vehiclesSummary,
                        'history_url' => "/dashboard/publishing/history/{$batch->id}",
                    ],
                ], JSON_PRETTY_PRINT),
            ],
        ];
    }
}

