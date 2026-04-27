<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantPermission;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get all available system permissions (grouped by category)
     * Useful for the role editor UI.
     */
    public function index(): JsonResponse
    {
        $permissions = TenantPermission::orderBy('category')->orderBy('label')->get();

        $grouped = $permissions->groupBy('category')->map(function ($group) {
            return $group->map(function ($perm) {
                return [
                    'id' => $perm->id,
                    'key' => $perm->key,
                    'label' => $perm->label,
                    'description' => $perm->description,
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Get the resolved permissions for the current user in the current tenant.
     */
    public function userPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (!$user || !$tenant) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $permissions = $this->permissionService->getUserPermissions($user, $tenant);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }
}
