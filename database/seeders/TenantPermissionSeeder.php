<?php

namespace Database\Seeders;

use App\Models\TenantPermission;
use App\Models\TenantRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define all available permissions
        $permissions = [
            // Inventory
            ['key' => 'inventory.view', 'label' => 'View Inventory', 'category' => 'Inventory'],
            ['key' => 'inventory.create', 'label' => 'Create Inventory', 'category' => 'Inventory'],
            ['key' => 'inventory.edit', 'label' => 'Edit Inventory Details', 'category' => 'Inventory'],
            ['key' => 'inventory.delete', 'label' => 'Delete Inventory', 'category' => 'Inventory'],
            ['key' => 'inventory.publish', 'label' => 'Publish/Unpublish', 'category' => 'Inventory'],
            ['key' => 'inventory.archive', 'label' => 'Archive Inventory', 'category' => 'Inventory'],
            ['key' => 'inventory.import', 'label' => 'Import Inventory', 'category' => 'Inventory'],
            ['key' => 'inventory.export', 'label' => 'Export Inventory', 'category' => 'Inventory'],
            ['key' => 'inventory.transfer', 'label' => 'Transfer Inventory', 'category' => 'Inventory'],

            // Inventory Media & Content
            ['key' => 'inventory.image.upload', 'label' => 'Upload Images', 'category' => 'Inventory'],
            ['key' => 'inventory.image.delete', 'label' => 'Delete Images', 'category' => 'Inventory'],
            ['key' => 'inventory.image.set_primary', 'label' => 'Set Primary Image', 'category' => 'Inventory'],
            ['key' => 'inventory.video.upload', 'label' => 'Upload Videos', 'category' => 'Inventory'],
            ['key' => 'inventory.video.delete', 'label' => 'Delete Videos', 'category' => 'Inventory'],
            ['key' => 'inventory.document.upload', 'label' => 'Upload Documents', 'category' => 'Inventory'],
            ['key' => 'inventory.document.delete', 'label' => 'Delete Documents', 'category' => 'Inventory'],

            // Inventory AI
            ['key' => 'inventory.ai.generate', 'label' => 'Trigger AI Generation', 'category' => 'Inventory'],
            ['key' => 'inventory.ai.analyze', 'label' => 'Run AI Analysis', 'category' => 'Inventory'],
            ['key' => 'inventory.ai.description', 'label' => 'Generate AI Description', 'category' => 'Inventory'],

            // Inventory Pricing
            ['key' => 'inventory.price.edit', 'label' => 'Edit Pricing', 'category' => 'Inventory'],
            ['key' => 'inventory.price.history', 'label' => 'View Price History', 'category' => 'Inventory'],

            // Acquisition & Costs
            ['key' => 'inventory.acquisition.view', 'label' => 'View Acquisition Details', 'category' => 'Dealership'],
            ['key' => 'inventory.acquisition.edit', 'label' => 'Edit Acquisition Details', 'category' => 'Dealership'],

            // Leads
            ['key' => 'leads.view', 'label' => 'View Vehicle Leads', 'category' => 'Dealership'],
            ['key' => 'leads.manage', 'label' => 'Manage Leads', 'category' => 'Dealership'],

            // Deals
            ['key' => 'deals.view', 'label' => 'View Deals', 'category' => 'Dealership'],
            ['key' => 'deals.manage', 'label' => 'Create & Edit Deals', 'category' => 'Dealership'],

            // Service & Reconditioning
            ['key' => 'service.view', 'label' => 'View Service & Recon', 'category' => 'Dealership'],
            ['key' => 'service.manage', 'label' => 'Manage Service & Recon Tasks', 'category' => 'Dealership'],

            // Publishing
            ['key' => 'publishing.view', 'label' => 'View Publishing Status', 'category' => 'Dealership'],
            ['key' => 'publishing.manage', 'label' => 'Manage External Publishing', 'category' => 'Dealership'],

            // Activity
            ['key' => 'activity.view', 'label' => 'View Activity Logs', 'category' => 'Activity'],
            ['key' => 'activity.view_all', 'label' => 'View All User Activity', 'category' => 'Activity'],

            // Workspace
            ['key' => 'workspace.settings', 'label' => 'Manage Workspace Settings', 'category' => 'Workspace'],
            ['key' => 'workspace.members', 'label' => 'Manage Members', 'category' => 'Workspace'],
            ['key' => 'workspace.roles', 'label' => 'Manage Roles & Permissions', 'category' => 'Workspace'],

            // System-level (low) permissions - managed by super admin
            ['key' => 'system.manage_roles', 'label' => 'Manage System Roles', 'category' => 'System', 'type' => 'low'],
            ['key' => 'system.manage_permissions', 'label' => 'Manage System Permissions', 'category' => 'System', 'type' => 'low'],
            ['key' => 'system.view_all_tenants', 'label' => 'View All Tenants', 'category' => 'System', 'type' => 'low'],
            ['key' => 'system.switch_tenant', 'label' => 'Switch Tenant (Admin)', 'category' => 'System', 'type' => 'low'],
        ];

        // Sync permissions
        $dbPerms = [];
        foreach ($permissions as $p) {
            $perm = TenantPermission::firstOrCreate(
                ['key' => $p['key']],
                ['label' => $p['label'], 'category' => $p['category'], 'type' => $p['type'] ?? 'high']
            );
            // Update label/category/type if changed
            if ($perm->label !== $p['label'] || $perm->category !== $p['category'] || $perm->type !== ($p['type'] ?? 'high')) {
                $perm->update(['label' => $p['label'], 'category' => $p['category'], 'type' => $p['type'] ?? 'high']);
            }
            $dbPerms[$p['key']] = $perm->id;
        }

        // 2. Define System Roles (tenant_id = null)
        $roles = [
            'owner' => [
                'name' => 'Owner',
                'level' => 50,
                'description' => 'Full access to all workspace features and settings.',
                'permissions' => ['*'], // All
            ],
            'admin' => [
                'name' => 'Admin',
                'level' => 40,
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'permissions' => ['!workspace.roles'], // All except roles
            ],
            'manager' => [
                'name' => 'Manager',
                'level' => 30,
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'permissions' => [
                    'inventory.view',
                    'inventory.create',
                    'inventory.edit',
                    'inventory.delete',
                    'inventory.publish',
                    'inventory.archive',
                    'inventory.import',
                    'inventory.export',
                    'inventory.transfer',
                    'inventory.image.upload',
                    'inventory.image.delete',
                    'inventory.image.set_primary',
                    'inventory.video.upload',
                    'inventory.video.delete',
                    'inventory.document.upload',
                    'inventory.document.delete',
                    'inventory.ai.generate',
                    'inventory.ai.analyze',
                    'inventory.ai.description',
                    'inventory.price.edit',
                    'inventory.price.history',
                    'inventory.acquisition.view',
                    'inventory.acquisition.edit',
                    'leads.view',
                    'leads.manage',
                    'deals.view',
                    'deals.manage',
                    'service.view',
                    'service.manage',
                    'publishing.view',
                    'publishing.manage',
                    'activity.view',
                ],
            ],
            'clerk' => [
                'name' => 'Clerk',
                'level' => 20,
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'permissions' => [
                    'inventory.view',
                    'inventory.create',
                    'inventory.edit',
                    'inventory.export',
                    'inventory.image.upload',
                    'inventory.image.set_primary',
                    'inventory.video.upload',
                    'inventory.document.upload',
                    'inventory.ai.description',
                    'inventory.price.history',
                    'inventory.acquisition.view',
                    'leads.view',
                    'deals.view',
                    'service.view',
                    'publishing.view',
                ],
            ],
            'viewer' => [
                'name' => 'Viewer',
                'level' => 10,
                'description' => 'Read-only access to inventory.',
                'permissions' => [
                    'inventory.view',
                    'inventory.price.history',
                ],
            ],
        ];

        // Separate high-level permission ids (used for tenant role templates)
        $highPermIds = [];
        foreach ($dbPerms as $key => $id) {
            $perm = TenantPermission::find($id);
            if ($perm && $perm->type === 'high') {
                $highPermIds[$key] = $id;
            }
        }

        foreach ($roles as $slug => $data) {
            $role = TenantRole::withoutGlobalScope('tenant')->firstOrCreate(
                [
                    'tenant_id' => null,
                    'slug' => $slug,
                ],
                [
                    'name' => $data['name'],
                    'level' => $data['level'],
                    'description' => $data['description'],
                    'is_system' => true,
                ]
            );
            // Update details if changed
            $role->update([
                'name' => $data['name'],
                'level' => $data['level'],
                'description' => $data['description'],
            ]);

            // Sync role permissions — templates store HIGH-level permissions
            // These get copied into each tenant via syncDefaultRoles()
            $permsToSync = [];
            if (in_array('*', $data['permissions'])) {
                // All high-level permissions
                $permsToSync = array_values($highPermIds);
            } elseif (!empty($data['permissions']) && str_starts_with($data['permissions'][0], '!')) {
                // Exclude pattern (e.g. ['!workspace.roles']) — all high-level except excluded
                $excludeKeys = array_map(fn($p) => substr($p, 1), $data['permissions']);
                foreach ($highPermIds as $key => $id) {
                    if (!in_array($key, $excludeKeys)) {
                        $permsToSync[] = $id;
                    }
                }
            } else {
                // Explicit list — match from high-level permissions
                foreach ($data['permissions'] as $key) {
                    if (isset($highPermIds[$key])) {
                        $permsToSync[] = $highPermIds[$key];
                    }
                }
            }

            $role->permissions()->sync($permsToSync);
        }
    }
}
