<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLead;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    /**
     * Store a new lead/enquiry from the dealer website.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'interested_vehicle_id' => 'required|exists:inventory_items,id',
            'intent' => 'nullable|string|in:test_drive,financing,contact_sales,general',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $vehicle = InventoryItem::find($request->interested_vehicle_id);

        $lead = ChatLead::create([
            'tenant_id' => $vehicle->tenant_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'intent' => $request->intent ?? 'general',
            'interested_vehicle_id' => $request->interested_vehicle_id,
            'notes' => $request->notes,
            'source' => 'email',
            'provider_name' => 'Dealer Website',
            'status' => 'new',
            'vehicle_details' => [
                'title' => $vehicle->title,
                'make' => $vehicle->generated_data['make'] ?? null,
                'model' => $vehicle->generated_data['model'] ?? null,
                'year' => $vehicle->generated_data['year'] ?? null,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enquiry submitted successfully',
            'lead_id' => $lead->id,
        ], 201);
    }
}
