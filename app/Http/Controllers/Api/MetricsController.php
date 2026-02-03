<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $range = $request->input('range', '30d');
        $now = now();

        // Determine start date and grouping interval
        match ($range) {
            '3m' => [
                $startDate = $now->copy()->subMinutes(3),
                $interval = 'minute', // Group by minute
                $format = 'H:i'
            ],
            '1h' => [
                $startDate = $now->copy()->subHour(),
                $interval = 'minute',
                $format = 'H:i'
            ],
            '24h' => [
                $startDate = $now->copy()->subDay(),
                $interval = 'hour',
                $format = 'H:i'
            ],
            '7d' => [
                $startDate = $now->copy()->subDays(7),
                $interval = 'day', // Actually day is fine, or hour if very detailed
                $format = 'M d'
            ],
            '30d' => [
                $startDate = $now->copy()->subDays(30),
                $interval = 'day',
                $format = 'M d'
            ],
            '1y' => [
                $startDate = $now->copy()->subYear(),
                $interval = 'month',
                $format = 'M Y'
            ],
            '2y' => [
                $startDate = $now->copy()->subYears(2),
                $interval = 'month',
                $format = 'M Y'
            ],
            default => [
                $startDate = $now->copy()->subDays(30),
                $interval = 'day',
                $format = 'M d'
            ],
        };

        // Base query scoped to user and date range
        $query = ApiUsageLog::where('user_id', $request->user()->id)
            ->where('created_at', '>=', $startDate);

        // Aggregate stats
        $totalRequests = (clone $query)->count();

        $errorCount = (clone $query)
            ->where('status_code', '>=', 400)
            ->count();

        $errorRate = $totalRequests > 0 ? round(($errorCount / $totalRequests) * 100, 2) : 0;

        $avgLatency = (clone $query)->avg('duration_ms');

        // Requests over time (chart data)
        // PostgreSQL: date_trunc(interval, timestamp)
        // SQLite: strftime (if running locally on sqlite, might need conditional, assuming pgsql based on user error)
        // User error showed: "invalid input syntax for type uuid" which is typical for PG, so we assume PG.
        $requestsOverTime = (clone $query)
            ->select(
                DB::raw("date_trunc('$interval', created_at) as date"),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) use ($format) {
                return [
                    'date' => \Carbon\Carbon::parse($item->date)->format($format),
                    'fullDate' => $item->date, // Keep full date for tooltip
                    'count' => $item->count,
                ];
            });

        // Top endpoints
        $topEndpoints = (clone $query)
            ->select('path', 'method', DB::raw('count(*) as count'))
            ->groupBy('path', 'method')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'totalRequests' => $totalRequests,
                    'errorRate' => $errorRate,
                    'avgLatencyMs' => round($avgLatency, 2),
                ],
                'chartData' => $requestsOverTime,
                'topEndpoints' => $topEndpoints,
            ]
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = ApiUsageLog::where('user_id', $request->user()->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'currentPage' => $logs->currentPage(),
                'lastPage' => $logs->lastPage(),
                'perPage' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
