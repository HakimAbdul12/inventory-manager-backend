<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\AcquisitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcquisitionController extends Controller
{
    protected AcquisitionService $acquisition;

    public function __construct(AcquisitionService $acquisition)
    {
        $this->acquisition = $acquisition;
    }

    /**
     * Get recommended vehicles to buy.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->acquisition->getRecommendations(),
        ]);
    }

    /**
     * Propose exit strategies for a specific aging unit.
     */
    public function proposeExit(string $vehicleId): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($vehicleId);

        return response()->json([
            'success' => true,
            'data' => $this->acquisition->proposeExit($vehicle),
        ]);
    }
}
