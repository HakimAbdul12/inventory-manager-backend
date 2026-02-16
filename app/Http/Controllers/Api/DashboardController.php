<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get War Room metrics.
     */
    public function warRoom(): JsonResponse
    {
        // 1. Units at Risk (High DOL, Low Velocity/Confidence)
        $unitsAtRisk = Vehicle::where('dol', '>', 30) // Over 30 days
            ->where('velocity_score', '<', 4)
            ->with('inventoryItem')
            ->limit(10)
            ->get();

        // 2. Profit Gaps (Underpriced units)
        $profitGaps = Vehicle::whereRaw('price < market_average')
            ->select('*', DB::raw('(market_average - price) as gap'))
            ->orderByDesc('gap')
            ->limit(10)
            ->get();

        // 3. Summary Stats
        $stats = [
            'total_inventory_value' => Vehicle::sum('price'),
            'avg_dol' => (int) Vehicle::avg('dol'),
            'coming_soon_count' => Vehicle::where('status', 'coming_soon')->count(),
            'active_count' => Vehicle::where('status', 'active')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'units_at_risk' => $unitsAtRisk,
                'profit_gaps' => $profitGaps,
                'stats' => $stats,
            ]
        ]);
    }
}
