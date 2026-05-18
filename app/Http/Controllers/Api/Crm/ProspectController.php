<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProspectController extends Controller
{
    /**
     * List and search prospects.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Prospect::query()
            ->with(['assignedUser'])
            ->withCount(['leads']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $prospects = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $prospects->items(),
            'pagination' => [
                'current_page' => $prospects->currentPage(),
                'per_page' => $prospects->perPage(),
                'total' => $prospects->total(),
                'last_page' => $prospects->lastPage(),
            ],
        ]);
    }

    /**
     * Show a single prospect with related entities.
     */
    public function show(string $id): JsonResponse
    {
        $prospect = Prospect::with([
            'assignedUser',
            'leads' => function ($q) {
                $q->with('assignedUser', 'interestedVehicle.images');
            },
            'leads.deals'
        ])->withCount(['leads'])->findOrFail($id);

        return response()->json([
            'data' => $prospect,
        ]);
    }
}
