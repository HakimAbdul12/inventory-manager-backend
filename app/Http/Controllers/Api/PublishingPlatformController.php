<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublishingPlatform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublishingPlatformController extends Controller
{
    /**
     * List all publishing platforms.
     * Super admins see all platforms; regular tenants see active platforms only.
     */
    public function index(Request $request): JsonResponse
    {
        $isSuperAdmin = (bool) $request->user()?->is_super_admin;

        $query = PublishingPlatform::query()->orderBy('sort_order', 'asc');

        if (!$isSuperAdmin) {
            $query->where('is_active', true);
        }

        $platforms = $query->get();

        return response()->json([
            'success' => true,
            'data' => $platforms,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }

    /**
     * Toggle a platform active/inactive (Super Admin only).
     */
    public function toggle(Request $request, string $key): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Only super administrators can modify platform availability.',
            ], 403);
        }

        $platform = PublishingPlatform::where('key', $key)->first();
        if (!$platform) {
            return response()->json([
                'success' => false,
                'message' => "Platform '{$key}' not found.",
            ], 404);
        }

        $newStatus = $request->has('is_active')
            ? (bool) $request->input('is_active')
            : !$platform->is_active;

        $platform->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Platform '{$platform->name}' " . ($newStatus ? 'activated' : 'deactivated') . " successfully.",
            'data' => $platform,
        ]);
    }
}
