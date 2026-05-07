<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::where('tenant_id', app('current_tenant')->id)
            ->findOrFail($id);

        $deals = $inventoryItem->deals()->with('lead')->get();

        return response()->json($deals);
    }
}
