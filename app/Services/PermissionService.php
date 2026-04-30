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
        // Super admin bypasses all checks
        if ($user->is_super_admin) {
            return true;
        }

        $tenant = $tenant ?? (app()->bound('current_tenant') ? app('current_tenant') : null);

        // If permission not defined, deny
        $perm = \App\Models\TenantPermission::where('key', $permissionKey)->first();
        if (!$perm) return false;

        // Low-level (system) permissions are only satisfied by system roles or super admin
        if ($perm->type === 'low') {
            // Check if user has a system role that contains this permission
            $has = \Illuminate\Support\Facades\DB::table('system_role_user')
                ->join('tenant_role_permissions', 'system_role_user.tenant_role_id', '=', 'tenant_role_permissions.tenant_role_id')
                ->join('tenant_permissions', 'tenant_role_permissions.tenant_permission_id', '=', 'tenant_permissions.id')
                ->where('system_role_user.user_id', $user->id)
                ->where('tenant_permissions.key', $permissionKey)
                ->exists();

            return $has;
        }

        // High-level (tenant-scoped) permissions require a tenant context
        if (!$tenant) return false;

        $permissions = $this->getUserPermissions($user, $tenant);
        return in_array($permissionKey, $permissions, true);
    }

    /**
     * Get all permission keys for a user within a tenant.
     * If $tenant is null, return system-level (low) permissions only.
     */
    public function getUserPermissions(User $user, ?Tenant $tenant): array
    {
        $tenantPart = $tenant ? $tenant->id : 'global';

        return Cache::remember("user_{$user->id}_tenant_{$tenantPart}_permissions", now()->addMinutes(60), function () use ($user, $tenant) {
            $perms = [];

            if ($tenant) {
                $tenantPermIds = \Illuminate\Support\Facades\DB::table('tenant_user_roles')
                    ->where('tenant_id', $tenant->id)
                    ->where('user_id', $user->id)
                    ->pluck('tenant_role_id');

                if ($tenantPermIds->isNotEmpty()) {
                    $tenantPerms = \Illuminate\Support\Facades\DB::table('tenant_role_permissions')
                        ->join('tenant_permissions', 'tenant_role_permissions.tenant_permission_id', '=', 'tenant_permissions.id')
                        ->whereIn('tenant_role_permissions.tenant_role_id', $tenantPermIds)
                        ->where('tenant_permissions.type', 'high')
                        ->pluck('tenant_permissions.key')
                        ->unique()
                        ->values()
                        ->toArray();

                    $perms = array_merge($perms, $tenantPerms);
                }
            }

            // System-level (low) permissions from system roles assigned to the user
            $systemRoleIds = \Illuminate\Support\Facades\DB::table('system_role_user')
                ->where('user_id', $user->id)
                ->pluck('tenant_role_id');

            if ($systemRoleIds->isNotEmpty()) {
                $systemPerms = \Illuminate\Support\Facades\DB::table('tenant_role_permissions')
                    ->join('tenant_permissions', 'tenant_role_permissions.tenant_permission_id', '=', 'tenant_permissions.id')
                    ->whereIn('tenant_role_permissions.tenant_role_id', $systemRoleIds)
                    ->where('tenant_permissions.type', 'low')
                    ->pluck('tenant_permissions.key')
                    ->unique()
                    ->values()
                    ->toArray();

                $perms = array_merge($perms, $systemPerms);
            }

            return array_values(array_unique($perms));
        });
    }

    /**
     * Clear cached permissions for a user in a tenant.
     * Call this when a user's roles change.
     */
    public function clearUserCache(User $user, ?Tenant $tenant): void
    {
        $tenantPart = $tenant ? $tenant->id : 'global';
        Cache::forget("user_{$user->id}_tenant_{$tenantPart}_permissions");
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
            // Only copy high-level permissions into tenant roles
            $permissionIds = $sysRole->permissions->where('type', 'high')->pluck('id')->toArray();
            $tenantRole->permissions()->sync($permissionIds);
        }
    }
}
