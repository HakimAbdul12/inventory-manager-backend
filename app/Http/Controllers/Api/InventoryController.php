<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryProcess;
use App\Services\AIContentService;
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
    protected AIContentService $aiService;

    public function __construct(
        InventoryGenerationService $generationService,
        ProcessTrackingService $trackingService,
        AIContentService $aiService
    ) {
        $this->generationService = $generationService;
        $this->trackingService = $trackingService;
        $this->aiService = $aiService;
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
            'options.virtualShowroomId' => 'nullable|string|exists:virtual_showrooms,id',
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
                    'fields' => $item->category->fields,
                ],
                'generatedData' => $item->generated_data,
                'images' => $item->images,
                'analysis_results' => $item->analysis_results,
                'confidence_score' => $item->confidence_score,
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
        // Use global tenant scope
        $query = InventoryItem::with(['category']);

        $this->applyFilters($query, $request);

        $items = $query->withCount('images')
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
                    'metadata' => $item->metadata,
                    'confidence_score' => $item->confidence_score,
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

    public function externalIndex(Request $request): JsonResponse
    {
        // Use global tenant scope
        $items = InventoryItem::with(['category'])
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
        // Use global tenant scope
        $processes = InventoryProcess::with(['category', 'steps'])
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
     * Get all inventory items for spreadsheet view (non-paginated).
     */
    public function allItems(Request $request): JsonResponse
    {
        $query = InventoryItem::with(['category', 'images']);
        $this->applyFilters($query, $request);

        $items = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                $data = $item->generated_data ?? [];
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'status' => $item->status,
                    'category' => $item->category->name,
                    'category_slug' => $item->category->slug,
                    'images_string' => $item->images->pluck('path')->implode('|'),
                    'createdAt' => $item->created_at->toIso8601String(),
                    'generatedData' => $data,
                    // Flattened common fields for convenience
                    'make' => $data['make'] ?? null,
                    'model' => $data['model'] ?? null,
                    'year' => $data['year'] ?? null,
                    'price' => $data['price'] ?? null,
                    'condition' => $data['condition'] ?? null,
                    'mileage' => $data['mileage'] ?? null,
                ];
            }),
        ]);
    }

    /**
     * Create a blank inventory item.
     */
    public function store(Request $request): JsonResponse
    {
        $category = Category::where('slug', 'cars')->first() ?? Category::first();

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'No categories available'], 422);
        }

        $item = InventoryItem::create([
            'category_id' => $category->id,
            'status' => 'draft',
            'generated_data' => [],
            'metadata' => [],
            'user_id' => $request->user()?->id ?? 'demo_user',
            'tenant_id' => $request->header('X-Tenant-Id') ?? null, // Fallback for demo
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'title' => 'New Listing',
                'status' => 'draft',
                'category' => $category->name,
                'category_slug' => $category->slug,
                'images_string' => '',
                'createdAt' => $item->created_at->toIso8601String(),
                'generatedData' => [],
            ]
        ], 201);
    }

    /**
     * Export inventory in various formats.
     */
    public function export(Request $request, string $format): \Symfony\Component\HttpFoundation\Response
    {
        $query = InventoryItem::with(['category', 'images']);
        $this->applyFilters($query, $request);

        $items = $query->get();
        $data = $items->map(function ($item) {
            $row = [
                'ID' => $item->id,
                'Title' => $item->title,
                'Status' => $item->status,
                'Category' => $item->category->name,
                'Created At' => $item->created_at->toIso8601String(),
                'Images' => $item->images->pluck('path')->implode('|'),
            ];

            // Merge generated data
            foreach ($item->generated_data ?? [] as $key => $value) {
                if (is_array($value)) $value = json_encode($value);
                $row[ucfirst($key)] = $value;
            }

            return $row;
        })->toArray();

        if ($format === 'json') {
            return response()->json($data);
        }

        if ($format === 'xml') {
            $xml = new \SimpleXMLElement('<inventory/>');
            foreach ($data as $item) {
                $node = $xml->addChild('item');
                foreach ($item as $key => $value) {
                    $key = str_replace(' ', '_', $key);
                    $node->addChild($key, htmlspecialchars((string)$value));
                }
            }
            return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
        }

        // Default to CSV
        $filename = "inventory_export_" . date('Y-m-d') . ".csv";
        $handle = fopen('php://memory', 'w');

        if (!empty($data)) {
            fputcsv($handle, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Update inventory item details (supports images_string).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        // Handle pipe-separated images
        if ($request->has('images_string')) {
            $urls = array_filter(explode('|', $request->input('images_string')));
            $urls = array_map('trim', $urls);

            // Delete images not in the new list
            $item->images()->whereNotIn('path', $urls)->delete();

            // Add new images
            foreach ($urls as $url) {
                if (!$item->images()->where('path', $url)->exists()) {
                    InventoryImage::create([
                        'inventory_item_id' => $item->id,
                        'path' => $url,
                        'is_primary' => $item->images()->count() === 0,
                        'processing_status' => InventoryImage::STATUS_COMPLETED,
                        'generated_by' => 'user_input',
                        'alt' => $item->title . ' - Image',
                        'sizes' => ['original' => $url],
                    ]);
                }
            }
        }

        // Merge existing data with new data
        $currentData = $item->generated_data ?? [];
        $newData = $request->input('generatedData', []);

        $mergedData = array_merge($currentData, $newData);

        $item->update(['generated_data' => $mergedData]);

        if ($request->has('status')) {
            $item->update(['status' => $request->status]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => [
                'id' => $item->id,
                'generatedData' => $item->generated_data,
                'status' => $item->status,
                'images_string' => $item->images()->pluck('path')->implode('|'),
            ],
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

    /**
     * Add an external image URL to an inventory item.
     */
    public function addExternalImage(Request $request, string $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $url = $request->input('url');

        // Create image record immediately with external URL
        // detailed processing could be added later if we wanted to download it
        $image = InventoryImage::create([
            'inventory_item_id' => $item->id,
            'path' => $url, // For external images, path is the URL
            'is_primary' => $item->images()->count() === 0,
            'processing_status' => InventoryImage::STATUS_COMPLETED, // It's already "there"
            'generated_by' => 'external_url',
            'alt' => $item->getTitleAttribute() . ' - External Image',
            'sizes' => ['original' => $url],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'External image added successfully',
            'data' => $image,
        ]);
    }

    /**
     * Analyze inventory item quality and market price.
     */
    public function analyze(string $id): JsonResponse
    {
        $item = InventoryItem::with('images')->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inventory item not found'], 404);
        }

        try {
            $analysis = $this->aiService->analyzeInventory($item);

            $item->update([
                'confidence_score' => $analysis['score'] ?? 0,
                'analysis_results' => $analysis,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'score' => $item->confidence_score,
                    'analysis' => $item->analysis_results,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate an AI description for the inventory item.
     */
    public function generateDescription(string $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inventory item not found'], 404);
        }

        try {
            $description = $this->aiService->generateDescription($item);

            return response()->json([
                'success' => true,
                'data' => [
                    'description' => $description,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Description generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Count inventory items matching filters (for push job preview).
     */
    public function count(Request $request): JsonResponse
    {
        $query = InventoryItem::query();
        $this->applyFilters($query, $request);

        return response()->json([
            'success' => true,
            'data' => ['count' => $query->count()],
        ]);
    }

    /**
     * Apply common filters to inventory query.
     */
    private function applyFilters($query, Request $request): void
    {
        // Filter by Status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by Make (JSON)
        if ($request->filled('make')) {
            $query->where('generated_data->make', 'like', '%' . $request->make . '%');
        }

        // Filter by Model (JSON)
        if ($request->filled('model')) {
            $query->where('generated_data->model', 'like', '%' . $request->model . '%');
        }

        // Filter by Year Range (JSON)
        if ($request->filled('min_year')) {
            $query->where('generated_data->year', '>=', (int)$request->min_year);
        }
        if ($request->filled('max_year')) {
            $query->where('generated_data->year', '<=', (int)$request->max_year);
        }

        // Filter by Price Range (JSON)
        if ($request->filled('min_price')) {
            $query->where('generated_data->price', '>=', (int)$request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('generated_data->price', '<=', (int)$request->max_price);
        }

        // Filter by Mileage Range (JSON)
        if ($request->filled('min_mileage')) {
            $query->where('generated_data->mileage', '>=', (int)$request->min_mileage);
        }
        if ($request->filled('max_mileage')) {
            $query->where('generated_data->mileage', '<=', (int)$request->max_mileage);
        }

        // Filter by Condition (JSON)
        if ($request->filled('condition') && $request->condition !== 'all') {
            $query->where('generated_data->condition', $request->condition);
        }

        // Filter by One-time Payment Available (JSON)
        if ($request->has('isOneTimePaymentAvailable')) {
            $query->where('generated_data->isOneTimePaymentAvailable', filter_var($request->isOneTimePaymentAvailable, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by Negotiable (JSON)
        if ($request->has('isNegotiable')) {
            $query->where('generated_data->isNegotiable', filter_var($request->isNegotiable, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by Lease Available (JSON)
        if ($request->has('isLeaseAvailable')) {
            $query->where('generated_data->isLeaseAvailable', filter_var($request->isLeaseAvailable, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by Financing Available (JSON)
        if ($request->has('isFinancingAvailable')) {
            $query->where('generated_data->isFinancingAvailable', filter_var($request->isFinancingAvailable, FILTER_VALIDATE_BOOLEAN));
        }
    }
}
