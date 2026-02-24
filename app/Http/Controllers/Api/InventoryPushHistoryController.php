<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryPushHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryPushHistoryController extends Controller
{
    /**
     * List push history with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryPushHistory::with('pushJob:id,name')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        // Filter by push job
        if ($request->filled('push_job_id')) {
            $query->where('push_job_id', $request->input('push_job_id'));
        }

        $history = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'currentPage' => $history->currentPage(),
                'lastPage' => $history->lastPage(),
                'perPage' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    /**
     * Get detailed history for a specific push execution.
     */
    public function show(int $id): JsonResponse
    {
        $history = InventoryPushHistory::with('pushJob')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
