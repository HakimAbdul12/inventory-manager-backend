<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\AINegotiatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AINegotiatorController extends Controller
{
    protected AINegotiatorService $negotiator;

    public function __construct(AINegotiatorService $negotiator)
    {
        $this->negotiator = $negotiator;
    }

    /**
     * Handle chat interaction with AI Sales Agent.
     */
    public function chat(Request $request, string $vehicleId): JsonResponse
    {
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => "I couldn't find details for this specific vehicle in our DIS system yet. Please contact the manager or try a newer listing."
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'lead_source' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->negotiator->chat(
            $vehicle,
            $request->input('message'),
            $request->input('lead_source', 'direct')
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
