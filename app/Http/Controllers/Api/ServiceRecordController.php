<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($id);

        $records = $inventoryItem->serviceRecords()->orderBy('date', 'desc')->get();

        return response()->json($records);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'service_type' => 'required|string|max:255',
            'date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $record = $inventoryItem->serviceRecords()->create(array_merge($validated, [
            'tenant_id' => app('current_tenant')->id,
        ]));

        return response()->json($record, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $inventoryId, string $recordId): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($inventoryId);

        $record = $inventoryItem->serviceRecords()
            ->where('tenant_id', app('current_tenant')->id)
            ->findOrFail($recordId);

        $validated = $request->validate([
            'service_type' => 'required|string|max:255',
            'date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $record->update($validated);

        return response()->json($record);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $inventoryId, string $recordId): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($inventoryId);

        $record = $inventoryItem->serviceRecords()
            ->where('tenant_id', app('current_tenant')->id)
            ->findOrFail($recordId);

        $record->delete();

        return response()->json(['message' => 'Service record deleted successfully']);
    }
}
