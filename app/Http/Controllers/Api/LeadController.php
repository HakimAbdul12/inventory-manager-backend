<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    /**
     * Store a new lead/enquiry from the dealer website.
     * Creates a unified Lead record (replaces old ChatLead creation).
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
        $nameParts = explode(' ', $request->name, 2);

        $lead = Lead::withoutGlobalScope('tenant')->create([
            'tenant_id' => $vehicle->tenant_id,
            'first_name' => $nameParts[0] ?? null,
            'last_name' => $nameParts[1] ?? null,
            'email' => $request->email,
            'phone' => $request->phone,
            'source_type' => Lead::SOURCE_WEBSITE,
            'source_name' => 'Dealer Website',
            'source' => 'website',
            'recorded_by_type' => Lead::RECORDED_BY_SYSTEM,
            'provider_name' => 'Dealer Website',
            'intent' => $request->intent ?? Lead::INTENT_GENERAL,
            'interested_vehicle_id' => $request->interested_vehicle_id,
            'vehicle_details' => [
                'title' => $vehicle->title,
                'make' => $vehicle->generated_data['make'] ?? null,
                'model' => $vehicle->generated_data['model'] ?? null,
                'year' => $vehicle->generated_data['year'] ?? null,
            ],
            'notes' => $request->notes,
            'status' => Lead::STATUS_NEW,
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enquiry submitted successfully',
            'lead_id' => $lead->id,
        ], 201);
    }
}
