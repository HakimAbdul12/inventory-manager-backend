<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Services\PricingEngineService;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    protected PricingEngineService $pricingEngine;
    protected ComplianceService $complianceService;

    public function __construct(PricingEngineService $pricingEngine, ComplianceService $complianceService)
    {
        $this->pricingEngine = $pricingEngine;
        $this->complianceService = $complianceService;
    }

    /**
     * Decode a VIN (Mocked).
     */
    public function decodeVin(Request $request): JsonResponse
    {
        $request->validate(['vin' => 'required|string|size:17']);

        // Mock decoding logic
        $vin = $request->vin;
        $mockData = [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2024,
            'trim' => 'XSE',
            'transmission' => 'Automatic',
            'engine' => '2.5L 4-Cyl',
            'market_average' => 32000,
        ];

        return response()->json(['success' => true, 'data' => $mockData]);
    }

    /**
     * Store a new vehicle and create Ghost VDP.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vin' => 'required|string|size:17|unique:vehicles',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'price' => 'nullable|numeric',
            'mileage' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $userId = $request->user()?->id ?? 1; // Fallback for demo
        $category = Category::where('slug', 'cars')->first();

        // 1. Create Ghost VDP (InventoryItem)
        $inventoryItem = InventoryItem::create([
            'id' => Str::uuid(),
            'user_id' => $userId,
            'category_id' => $category?->id ?? Str::uuid(), // Ensure it has a category
            'status' => 'coming_soon',
            'generated_data' => [
                'vin' => $request->vin,
                'make' => $request->make,
                'model' => $request->model,
                'year' => $request->year,
                'title' => "{$request->year} {$request->make} {$request->model} (Coming Soon)",
                'description' => "Stay tuned! Detailed specifications and photos for this {$request->make} {$request->model} are coming soon.",
            ],
            'process_id' => Str::uuid(), // Placeholder
        ]);

        // 2. Create Vehicle
        $vehicle = Vehicle::create([
            'id' => Str::uuid(),
            'inventory_item_id' => $inventoryItem->id,
            'user_id' => $userId,
            'vin' => $request->vin,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'price' => $request->price,
            'mileage' => $request->mileage,
            'market_average' => $request->market_average ?? 30000,
            'velocity_score' => 5.0, // Default mid-range
            'carrying_cost' => 15.00, // $15/day avg
            'dol' => 0,
            'status' => 'coming_soon',
        ]);

        // 3. Initial Pricing Calculation
        $initialPrice = $this->pricingEngine->calculatePFP($vehicle);
        $vehicle->update(['price' => $initialPrice]);

        return response()->json([
            'success' => true,
            'data' => [
                'vehicle' => $vehicle,
                'inventory_item_id' => $inventoryItem->id
            ]
        ], 201);
    }

    /**
     * Get vehicle details with pricing and compliance.
     */
    public function show(string $id): JsonResponse
    {
        $vehicle = Vehicle::with(['inventoryItem', 'pricingHistories', 'complianceAudits'])->find($id);

        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $vehicle]);
    }

    /**
     * Trigger a pricing update.
     */
    public function updatePricing(Request $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }

        $newPrice = $this->pricingEngine->calculatePFP($vehicle);
        $this->pricingEngine->updatePrice($vehicle, $newPrice, 'AI-Triggered Dynamic Pricing Update', $request->user()?->id);

        return response()->json(['success' => true, 'new_price' => $newPrice]);
    }

    /**
     * List all vehicles.
     */
    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::with('inventoryItem')->latest()->paginate(15);
        return response()->json(['success' => true, 'data' => $vehicles]);
    }
}
