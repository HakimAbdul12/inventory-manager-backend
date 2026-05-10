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
        $request = request();
        $type = $request->query('type'); // optional: 'high' or 'low'

        $query = TenantPermission::orderBy('category')->orderBy('label');
        if ($type && in_array($type, ['high', 'low'])) {
            $query->where('type', $type);
        }

        $permissions = $query->get();

        $grouped = $permissions->groupBy('category')->map(function ($group) {
            return $group->map(function ($perm) {
                return [
                    'id' => $perm->id,
                    'key' => $perm->key,
                    'label' => $perm->label,
                    'description' => $perm->description,
                    'type' => $perm->type,
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

    /**
     * Create a new permission (Super Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->is_super_admin) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'key' => 'required|string|unique:tenant_permissions,key',
            'label' => 'required|string',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|in:high,low',
        ]);

        $permission = TenantPermission::create($validated);

        // Automatically assign new permission to the Super Admin system role if it exists
        $superAdminRole = \App\Models\TenantRole::withoutGlobalScope('tenant')
            ->whereNull('tenant_id')
            ->where('slug', 'super_admin')
            ->first();

        if ($superAdminRole) {
            $superAdminRole->permissions()->attach($permission->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission,
        ]);
    }

    /**
     * Update an existing permission (Super Admin only).
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$request->user() || !$request->user()->is_super_admin) {
            abort(403, 'Unauthorized.');
        }

        $permission = TenantPermission::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|unique:tenant_permissions,key,' . $permission->id,
            'label' => 'required|string',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|in:high,low',
        ]);

        $permission->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission,
        ]);
    }

    /**
     * Delete a permission (Super Admin only).
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        if (!$request->user() || !$request->user()->is_super_admin) {
            abort(403, 'Unauthorized.');
        }

        $permission = TenantPermission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ]);
    }
}
