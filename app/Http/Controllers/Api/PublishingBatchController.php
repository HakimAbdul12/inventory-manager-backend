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
            ->get(['id']);

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
                $firstItem = null;
                foreach ($validPlatforms as $index => $platform) {
                    $isFirst = ($index === 0);
                    $batchItem = PublishingBatchItem::create([
                        'batch_id' => $batch->id,
                        'inventory_item_id' => $item->id,
                        'platform_key' => $platform['id'],
                        'format' => $platform['format'] ?? 'image',
                        'status' => $isFirst ? 'in_progress' : 'pending',
                        'attempts' => $isFirst ? 1 : 0,
                        'max_attempts' => 3,
                        'last_attempted_at' => $isFirst ? now() : null,
                    ]);

                    if ($isFirst) {
                        $firstItem = $batchItem;
                    }
                }

                // Dispatch the first platform job; subsequent platforms will trigger sequentially on completion
                if ($firstItem) {
                    ProcessPublishingItemJob::dispatch($firstItem->id);
                }
            }

            return $batch;
        });

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
