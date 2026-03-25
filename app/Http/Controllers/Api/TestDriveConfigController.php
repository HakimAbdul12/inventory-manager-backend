<?php

namespace App\Http\Controllers\Api;

use App\Models\TestDriveConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TestDriveConfigController extends Controller
{
    /**
     * Get the current tenant's test drive configuration.
     */
    public function show(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');
        $config = TestDriveConfig::where('tenant_id', $tenantId)->first();

        if (!$config) {
            // Return defaults if no config exists yet
            return response()->json([
                'success' => true,
                'data' => [
                    'is_active' => false,
                    'max_per_day' => null,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'duration_minutes' => 15,
                    'buffer_minutes' => 0,
                    'max_concurrent' => 1,
                    'available_days' => [1, 2, 3, 4, 5],
                    'blocked_dates' => [],
                ],
            ]);
        }

        return response()->json(['success' => true, 'data' => $config]);
    }

    /**
     * Create or update the test drive configuration.
     */
    public function update(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $validated = $request->validate([
            'is_active' => 'sometimes|boolean',
            'max_per_day' => 'sometimes|nullable|integer|min:1',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'duration_minutes' => 'sometimes|integer|in:15,30,45,60',
            'buffer_minutes' => 'sometimes|integer|min:0|max:60',
            'max_concurrent' => 'sometimes|integer|min:1|max:50',
            'available_days' => 'sometimes|array',
            'available_days.*' => 'integer|min:0|max:6',
            'blocked_dates' => 'sometimes|nullable|array',
            'blocked_dates.*' => 'date',
        ]);

        $config = TestDriveConfig::updateOrCreate(
            ['tenant_id' => $tenantId],
            $validated
        );

        return response()->json(['success' => true, 'data' => $config]);
    }
}
