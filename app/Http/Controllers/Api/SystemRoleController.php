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

        $added = [];
        $removed = [];

        if (array_key_exists('permissions', $validated)) {
            $oldPermsModels = $role->permissions()->get();
            $oldPerms = $oldPermsModels->pluck('key')->toArray();
            
            $perms = TenantPermission::whereIn('key', $validated['permissions'])->get();
            $newPerms = $perms->pluck('key')->toArray();
            
            $addedKeys = array_values(array_diff($newPerms, $oldPerms));
            $removedKeys = array_values(array_diff($oldPerms, $newPerms));
            
            $added = $perms->whereIn('key', $addedKeys)->pluck('label')->toArray();
            $removed = $oldPermsModels->whereIn('key', $removedKeys)->pluck('label')->toArray();

            $role->permissions()->sync($perms->pluck('id'));

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

        // Get all users holding this system role template across tenants to notify them
        $clonedRoleIds = TenantRole::withoutGlobalScope('tenant')
            ->whereNotNull('tenant_id')
            ->where('is_system', true)
            ->where('slug', $role->slug)
            ->pluck('id');

        $usersToNotify = \Illuminate\Support\Facades\DB::table('tenant_user_roles')
            ->whereIn('tenant_role_id', $clonedRoleIds)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $title = 'Role Permissions Updated';
        $body = "The system role template \"{$role->name}\" has been updated.";
        
        if (!empty($added) || !empty($removed)) {
            $changes = [];
            if (!empty($added)) {
                $changes[] = 'Gained: ' . implode(', ', $added);
            }
            if (!empty($removed)) {
                $changes[] = 'Lost: ' . implode(', ', $removed);
            }
            $body = "The permissions for your role \"{$role->name}\" have changed. " . implode('. ', $changes) . '.';
        }

        if (!empty($usersToNotify)) {
            try {
                app(\App\Services\NotificationService::class)->send(
                    \App\DTOs\NotificationData::fromArray([
                        'title' => $title,
                        'body' => $body,
                        'category' => 'system',
                        'actionUrl' => null,
                        'senderId' => null, // Unattributed system notification
                        'tenantId' => null, // Global
                        'subjectType' => TenantRole::class,
                        'subjectId' => $role->id,
                        'metadata' => [
                            'role_name' => $role->name,
                        ]
                    ]),
                    [
                        'user_ids' => $usersToNotify
                    ]
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send system role update notification: ' . $e->getMessage());
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
