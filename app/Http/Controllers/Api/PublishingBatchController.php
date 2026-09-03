<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPublishingItemJob;
use App\Models\InventoryItem;
use App\Models\PublishingBatch;
use App\Models\PublishingBatchItem;
use App\Models\PublishingPlatform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublishingBatchController extends Controller
{
    /**
     * List all publishing batches for the current workspace.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'No active tenant.'], 400);
        }

        $batches = PublishingBatch::where('tenant_id', $tenant->id)
            ->with(['user:id,name,email', 'items' => function ($q) {
                $q->select('id', 'batch_id', 'inventory_item_id', 'platform_key', 'status', 'attempts');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $batches->items(),
            'pagination' => [
                'currentPage' => $batches->currentPage(),
                'lastPage' => $batches->lastPage(),
                'perPage' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ]);
    }

    /**
     * Get the latest active in-progress publishing batch for the workspace.
     */
    public function active(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'No active tenant.'], 400);
        }

        $activeBatch = PublishingBatch::where('tenant_id', $tenant->id)
            ->where('status', 'in_progress')
            ->with(['items' => function ($q) {
                $q->with('inventoryItem:id,generated_data');
            }])
            ->latest()
            ->first();

        if (!$activeBatch) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $activeItem = $activeBatch->items->firstWhere('status', 'in_progress');

        return response()->json([
            'success' => true,
            'data' => [
                'batch_id' => $activeBatch->id,
                'status' => $activeBatch->status,
                'total_items' => $activeBatch->total_items,
                'successful_items' => $activeBatch->successful_items,
                'failed_items' => $activeBatch->failed_items,
                'current_vehicle_title' => $activeItem?->inventoryItem?->generated_data['title'] ?? null,
                'current_platform' => $activeItem?->platform_key ?? null,
                'created_at' => $activeBatch->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create and trigger a new publishing batch.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'No active tenant.'], 400);
        }

        $validated = $request->validate([
            'inventory_ids' => 'required|array|min:1',
            'inventory_ids.*' => 'required|string',
            'platforms' => 'required|array|min:1',
            'platforms.*.id' => 'required|string',
            'platforms.*.format' => 'nullable|string|in:image,video',
        ]);

        $inventoryIds = $validated['inventory_ids'];
        $selectedPlatforms = $validated['platforms'];

        // Filter valid vehicles belonging to tenant
        $items = InventoryItem::where('tenant_id', $tenant->id)
            ->whereIn('id', $inventoryIds)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid inventory items found for this workspace.',
            ], 422);
        }

        // Filter active platforms
        $activePlatformKeys = PublishingPlatform::active()->pluck('key')->toArray();
        $validPlatforms = array_filter($selectedPlatforms, fn($p) => in_array($p['id'], $activePlatformKeys));

        if (empty($validPlatforms)) {
            return response()->json([
                'success' => false,
                'message' => 'None of the selected platforms are currently active.',
            ], 422);
        }

        // Update all selected vehicles to published status
        InventoryItem::where('tenant_id', $tenant->id)
            ->whereIn('id', $inventoryIds)
            ->update(['status' => InventoryItem::STATUS_PUBLISHED]);

        $totalTasks = count($items) * count($validPlatforms);

        $batch = DB::transaction(function () use ($tenant, $request, $items, $validPlatforms, $totalTasks) {
            $batch = PublishingBatch::create([
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()?->id,
                'status' => 'in_progress',
                'total_items' => $totalTasks,
                'successful_items' => 0,
                'failed_items' => 0,
                'metadata' => [
                    'vehicles_count' => count($items),
                    'platforms_count' => count($validPlatforms),
                    'platforms' => array_column($validPlatforms, 'id'),
                ],
            ]);

            foreach ($items as $item) {
                $firstItemToDispatch = null;
                $vehicleData = $item->generated_data ?? [];

                foreach ($validPlatforms as $platform) {
                    $platformKey = $platform['id'];

                    // 1. Deduplication: Check if already published previously
                    $alreadyPublished = \App\Models\InventoryPublishingStatus::where('tenant_id', $tenant->id)
                        ->where('inventory_item_id', $item->id)
                        ->where('platform_name', $platformKey)
                        ->where('status', 'success')
                        ->exists();

                    if (!$alreadyPublished) {
                        $alreadyPublished = PublishingBatchItem::where('inventory_item_id', $item->id)
                            ->where('platform_key', $platformKey)
                            ->where('status', 'published')
                            ->exists();
                    }

                    if ($alreadyPublished) {
                        PublishingBatchItem::create([
                            'batch_id' => $batch->id,
                            'inventory_item_id' => $item->id,
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

                    // 2. Platform rules: OnlyEV requires EV/Hybrid
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
                                'inventory_item_id' => $item->id,
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

                    // 3. Normal queueable platform item
                    $isFirstPending = ($firstItemToDispatch === null);
                    $batchItem = PublishingBatchItem::create([
                        'batch_id' => $batch->id,
                        'inventory_item_id' => $item->id,
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

                // Dispatch the first pending platform job for this vehicle
                if ($firstItemToDispatch) {
                    ProcessPublishingItemJob::dispatch($firstItemToDispatch->id);
                }
            }

            $batch->updateProgress();
            return $batch;
        });

        // Broadcast initial status update on tenant channel
        $firstItem = $batch->items()->first();
        if ($firstItem) {
            try {
                event(new \App\Events\PublishingItemStatusUpdated(
                    $firstItem,
                    ['message' => 'Publishing batch initiated.']
                ));
            } catch (\Throwable $e) {
                // Ignore broadcast error
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Publishing batch initiated successfully.',
            'data' => [
                'batch_id' => $batch->id,
                'status' => $batch->status,
                'total_items' => $batch->total_items,
            ],
        ], 201);
    }

    /**
     * Get real-time status and vehicle items for a publishing batch.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenant = app('current_tenant');
        $batch = PublishingBatch::where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->with(['user:id,name,email'])
            ->first();

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Publishing batch not found.'], 404);
        }

        // Fetch batch items with vehicle details & images
        $items = PublishingBatchItem::where('batch_id', $batch->id)
            ->with([
                'inventoryItem' => function ($q) {
                    $q->select('id', 'tenant_id', 'status', 'generated_data', 'metadata')
                      ->with(['images' => fn($img) => $img->orderBy('is_primary', 'desc')->limit(1)]);
                },
            ])
            ->get();

        // Group items by vehicle for clean pipeline representation
        $vehiclesGrouped = [];
        foreach ($items as $item) {
            $invId = $item->inventory_item_id;
            if (!isset($vehiclesGrouped[$invId])) {
                $inv = $item->inventoryItem;
                $gen = $inv?->generated_data ?? [];
                $primaryImage = $inv?->images?->first();

                $vehiclesGrouped[$invId] = [
                    'id' => $invId,
                    'title' => $gen['title'] ?? (trim(($gen['year'] ?? '') . ' ' . ($gen['make'] ?? '') . ' ' . ($gen['model'] ?? '')) ?: 'Vehicle Listing'),
                    'price' => $gen['price'] ?? null,
                    'vin' => $gen['vin'] ?? null,
                    'stock_number' => $gen['stock_number'] ?? null,
                    'image_url' => $primaryImage ? ($primaryImage->url ?: $primaryImage->path) : null,
                    'platforms' => [],
                ];
            }

            $vehiclesGrouped[$invId]['platforms'][] = [
                'batch_item_id' => $item->id,
                'platform_key' => $item->platform_key,
                'format' => $item->format,
                'status' => $item->status,
                'attempts' => $item->attempts,
                'max_attempts' => $item->max_attempts,
                'error_message' => $item->error_message,
                'last_attempted_at' => $item->last_attempted_at?->toIso8601String(),
                'published_at' => ($item->status === 'published' ? ($item->last_attempted_at ?? $item->updated_at)?->toIso8601String() : null),
            ];
        }

        $progressPercent = $batch->total_items > 0
            ? round((($batch->successful_items + $batch->failed_items) / $batch->total_items) * 100)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'batch' => $batch,
                'progress_percent' => $progressPercent,
                'vehicles' => array_values($vehiclesGrouped),
                'items_count' => $items->count(),
            ],
        ]);
    }

    /**
     * Retry a single failed publishing item.
     */
    public function retry(Request $request, string $batchId, string $itemId): JsonResponse
    {
        $tenant = app('current_tenant');
        $batch = PublishingBatch::where('id', $batchId)->where('tenant_id', $tenant->id)->first();
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
        }

        $batchItem = PublishingBatchItem::where('id', $itemId)->where('batch_id', $batch->id)->first();
        if (!$batchItem) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $batchItem->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
        ]);

        $batch->updateProgress();

        ProcessPublishingItemJob::dispatch($batchItem->id);

        return response()->json([
            'success' => true,
            'message' => 'Publishing task re-queued for processing.',
            'data' => $batchItem,
        ]);
    }
}
