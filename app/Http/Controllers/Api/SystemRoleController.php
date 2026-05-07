<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SystemRoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = TenantRole::withoutGlobalScope('tenant')
            ->whereNull('tenant_id')
            ->with('permissions:id,key')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'description' => $r->description,
                'level' => $r->level,
                'permissions' => $r->permissions->pluck('key'),
            ]);

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->is_super_admin) {
            return response()->json(['message' => 'Only super admin may manage system roles.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:tenant_permissions,key'],
            'level' => ['integer'],
        ]);

        $slug = $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']);

        $role = TenantRole::withoutGlobalScope('tenant')->create([
            'tenant_id' => null,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_system' => true,
            'level' => $validated['level'] ?? 0,
        ]);

        if (!empty($validated['permissions'])) {
            $perms = TenantPermission::whereIn('key', $validated['permissions'])->get();
            $role->permissions()->sync($perms->pluck('id'));
        }

        return response()->json(['message' => 'System role created.', 'data' => $role->load('permissions:id,key')], 201);
    }

    public function update(Request $request, string $roleId): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->is_super_admin) {
            return response()->json(['message' => 'Only super admin may manage system roles.'], 403);
        }

        $role = TenantRole::withoutGlobalScope('tenant')->whereNull('tenant_id')->findOrFail($roleId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:tenant_permissions,key'],
            'level' => ['sometimes', 'integer'],
        ]);

        $update = [];
        if (isset($validated['name'])) $update['name'] = $validated['name'];
        if (array_key_exists('description', $validated)) $update['description'] = $validated['description'];
        if (isset($validated['level'])) $update['level'] = $validated['level'];

        if (!empty($update)) $role->update($update);

        if (array_key_exists('permissions', $validated)) {
            $perms = TenantPermission::whereIn('key', $validated['permissions'])->get();
            $role->permissions()->sync($perms->pluck('id'));

            // Cascade permissions to all existing tenant roles cloned from this template
            $tenantRoles = TenantRole::withoutGlobalScope('tenant')
                ->whereNotNull('tenant_id')
                ->where('is_system', true)
                ->where('slug', $role->slug)
                ->get();

            foreach ($tenantRoles as $tenantRole) {
                $tenantRole->permissions()->sync($perms->pluck('id'));
                
                // Clear cache for users in this tenant role
                $userIds = \Illuminate\Support\Facades\DB::table('tenant_user_roles')
                    ->where('tenant_role_id', $tenantRole->id)
                    ->pluck('user_id');
                    
                foreach ($userIds as $userId) {
                    \Illuminate\Support\Facades\Cache::forget("user_{$userId}_tenant_{$tenantRole->tenant_id}_permissions");
                }
            }
        }

        event(new \App\Events\SystemPermissionsUpdated());

        return response()->json(['message' => 'System role updated.']);
    }

    public function destroy(Request $request, string $roleId): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->is_super_admin) {
            return response()->json(['message' => 'Only super admin may manage system roles.'], 403);
        }

        $role = TenantRole::withoutGlobalScope('tenant')->whereNull('tenant_id')->findOrFail($roleId);

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete a role assigned to users.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'System role deleted.']);
    }
}
