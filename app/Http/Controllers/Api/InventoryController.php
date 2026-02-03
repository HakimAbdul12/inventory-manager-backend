<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryProcess;
use App\Services\InventoryGenerationService;
use App\Services\ProcessTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\InventoryImage;
use App\Jobs\ProcessInventoryImageJob;

class InventoryController extends Controller
{
    protected InventoryGenerationService $generationService;
    protected ProcessTrackingService $trackingService;

    public function __construct(
        InventoryGenerationService $generationService,
        ProcessTrackingService $trackingService
    ) {
        $this->generationService = $generationService;
        $this->trackingService = $trackingService;
    }

    /**
     * Start a new inventory generation process.
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'categorySlug' => 'required|string|exists:categories,slug',
            'userInputs' => 'required|array',
            'customPrompt' => 'nullable|string|max:2000',
            'options' => 'nullable|array',
            'options.generateImages' => 'nullable|boolean',
            'options.imageCount' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = Category::where('slug', $request->categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found or inactive',
            ], 404);
        }

        try {
            // For demo purposes, use a static user ID
            // In production, get from authenticated user: auth()->id()
            $userId = $request->user()?->id ?? 'demo_user';

            $process = $this->generationService->startGeneration(
                userId: $userId,
                category: $category,
                userInputs: $request->input('userInputs', []),
                customPrompt: $request->input('customPrompt'),
                options: $request->input('options', [])
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'processId' => $process->id,
                    'status' => $process->status->value,
                    'steps' => $process->steps->map(fn($step) => [
                        'name' => $step->step_name,
                        'order' => $step->step_order,
                        'status' => $step->status->value,
                    ])->toArray(),
                    'channelName' => $process->getBroadcastChannelName(),
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start generation process',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get the status of a generation process.
     */
    public function status(string $processId): JsonResponse
    {
        $process = InventoryProcess::with(['steps', 'category', 'inventoryItem'])
            ->find($processId);

        if (!$process) {
            return response()->json([
                'success' => false,
                'message' => 'Process not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->trackingService->getProcessStatus($process),
        ]);
    }

    /**
     * Get a completed inventory item.
     */
    public function show(string $id): JsonResponse
    {
        $item = InventoryItem::with(['category', 'process'])
            ->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'status' => $item->status,
                'category' => [
                    'name' => $item->category->name,
                    'slug' => $item->category->slug,
                ],
                'generatedData' => $item->generated_data,
                'images' => $item->images,
                'metadata' => $item->metadata,
                'createdAt' => $item->created_at->toIso8601String(),
                'updatedAt' => $item->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * List user's inventory items.
     */
    public function index(Request $request): JsonResponse
    {
        // For demo purposes, use a static user ID
        $userId = $request->user()?->id ?? 'demo_user';

        $items = InventoryItem::where('user_id', $userId)
            ->with(['category'])
            ->withCount('images')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                $data = $item->generated_data ?? [];
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'status' => $item->status,
                    'category' => $item->category->name,
                    'primaryImage' => $item->primary_image,
                    'createdAt' => $item->created_at->toIso8601String(),
                    // Additional fields from generated data
                    'make' => $data['make'] ?? null,
                    'model' => $data['model'] ?? null,
                    'year' => $data['year'] ?? null,
                    'price' => $data['price'] ?? null,
                    'condition' => $data['condition'] ?? null,
                    'mileage' => $data['mileage'] ?? null,
                    'color' => $data['color'] ?? null,
                    'description' => isset($data['description'])
                        ? \Illuminate\Support\Str::limit($data['description'], 120)
                        : null,
                    'imageCount' => $item->images_count,
                ];
            }),
            'pagination' => [
                'currentPage' => $items->currentPage(),
                'lastPage' => $items->lastPage(),
                'perPage' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * List user's generation processes.
     */
    public function processes(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 'demo_user';

        $processes = InventoryProcess::where('user_id', $userId)
            ->with(['category', 'steps'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $processes->map(fn($process) => [
                'id' => $process->id,
                'category' => $process->category->name,
                'status' => $process->status->value,
                'currentStep' => $process->current_step,
                'inventoryItemId' => $process->inventory_item_id,
                'createdAt' => $process->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Update inventory item details.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        // Merge existing data with new data
        $currentData = $item->generated_data ?? [];
        $newData = $request->input('generatedData', []);

        $mergedData = array_merge($currentData, $newData);

        $item->update(['generated_data' => $mergedData]);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item->refresh(),
        ]);
    }

    /**
     * Upload an image for an inventory item.
     */
    public function uploadImage(Request $request, string $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'image_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = "inventory/{$item->id}/{$filename}";

            Storage::disk('public')->put($path, file_get_contents($file));

            $image = InventoryImage::create([
                'inventory_item_id' => $item->id,
                'path' => $path,
                'is_primary' => $item->images()->count() === 0,
                'processing_status' => InventoryImage::STATUS_PENDING,
                'generated_by' => 'user_upload',
                'alt' => $item->title . ' - Uploaded Image',
                'sizes' => ['original' => Storage::url($path)],
            ]);

            ProcessInventoryImageJob::dispatch($image)
                ->onQueue(config('inventory.queue.name', 'inventory'));

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $image,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No image file provided'], 400);
    }

    /**
     * Set an image as primary.
     */
    public function setPrimaryImage(Request $request, string $id, string $imageId): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $image = InventoryImage::where('inventory_item_id', $id)->find($imageId);

        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Image not found'], 404);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($id, $imageId) {
            InventoryImage::where('inventory_item_id', $id)
                ->update(['is_primary' => false]);

            InventoryImage::where('id', $imageId)
                ->update(['is_primary' => true]);
        });

        return response()->json(['success' => true, 'message' => 'Primary image updated']);
    }

    /**
     * Delete an image.
     */
    public function deleteImage(Request $request, string $id, string $imageId): JsonResponse
    {
        $image = InventoryImage::where('inventory_item_id', $id)->find($imageId);

        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Image not found'], 404);
        }

        if (Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $newPrimary = InventoryImage::where('inventory_item_id', $id)->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Image deleted']);
    }
}
