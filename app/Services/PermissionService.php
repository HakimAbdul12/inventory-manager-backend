<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Check if a user has a specific permission within a tenant.
     */
    public function userCan(string $permissionKey, User $user, ?Tenant $tenant = null): bool
    {
        $tenant = $tenant ?? app()->bound('current_tenant') ? app('current_tenant') : null;

        if (!$tenant) {
            return false;
        }

        $permissions = $this->getUserPermissions($user, $tenant);

        return in_array($permissionKey, $permissions);
    }

    /**
     * Get all permission keys for a user within a tenant.
     */
    public function getUserPermissions(User $user, Tenant $tenant): array
    {
        $cacheKey = "user_{$user->id}_tenant_{$tenant->id}_permissions";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user, $tenant) {
            // Get user's roles in this tenant
            $roleIds = \Illuminate\Support\Facades\DB::table('tenant_user_roles')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->pluck('tenant_role_id');

            if ($roleIds->isEmpty()) {
                // Fallback: check legacy pivot role if it exists
                $legacyRole = $tenant->pivot->role ?? \Illuminate\Support\Facades\DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->where('user_id', $user->id)
                    ->value('role');

                if ($legacyRole) {
                    // Map legacy role string to permissions via system template
                    $systemRole = TenantRole::withoutGlobalScope('tenant')
                        ->whereNull('tenant_id')
                        ->where('slug', $legacyRole)
                        ->first();

                    if ($systemRole) {
                        return $systemRole->permissions()->pluck('key')->toArray();
                    }
                }
                return [];
            }

            // Get all unique permission keys from these roles
            return \Illuminate\Support\Facades\DB::table('tenant_role_permissions')
                ->join('tenant_permissions', 'tenant_role_permissions.tenant_permission_id', '=', 'tenant_permissions.id')
                ->whereIn('tenant_role_permissions.tenant_role_id', $roleIds)
                ->pluck('tenant_permissions.key')
                ->unique()
                ->values()
                ->toArray();
        });
    }

    /**
     * Clear cached permissions for a user in a tenant.
     * Call this when a user's roles change.
     */
    public function clearUserCache(User $user, Tenant $tenant): void
    {
        Cache::forget("user_{$user->id}_tenant_{$tenant->id}_permissions");
    }

    /**
     * Seed default roles for a newly created tenant based on system templates.
     */
    public function syncDefaultRoles(Tenant $tenant): void
    {
        $systemRoles = TenantRole::withoutGlobalScope('tenant')
            ->whereNull('tenant_id')
            ->with('permissions')
            ->get();

        foreach ($systemRoles as $sysRole) {
            // Create tenant-specific copy
            $tenantRole = TenantRole::create([
                'tenant_id' => $tenant->id,
                'name' => $sysRole->name,
                'slug' => $sysRole->slug,
                'description' => $sysRole->description,
                'is_system' => true,
                'level' => $sysRole->level,
            ]);

            // Sync permissions
            $permissionIds = $sysRole->permissions->pluck('id')->toArray();
            $tenantRole->permissions()->sync($permissionIds);
        }
    }
}
