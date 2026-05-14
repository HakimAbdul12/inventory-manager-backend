<?php

$systemRoles = \App\Models\TenantRole::whereNull('tenant_id')->with('permissions')->get();

foreach ($systemRoles as $sysRole) {
    $tenantRoles = \App\Models\TenantRole::whereNotNull('tenant_id')
        ->where('slug', $sysRole->slug)
        ->get();

    $permissionIds = $sysRole->permissions->pluck('id')->toArray();

    foreach ($tenantRoles as $tRole) {
        $tRole->permissions()->sync($permissionIds);
    }
}

echo "Successfully synced updated permissions to all existing tenant roles.\n";
