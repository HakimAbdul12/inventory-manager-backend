<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'super@admin.local');
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');

        $user = User::firstOrCreate([
            'email' => $email,
        ], [
            'name' => $name,
            'password' => Hash::make($password),
            'is_super_admin' => true,
        ]);

        if (!$user->is_super_admin) {
            $user->update(['is_super_admin' => true]);
        }

        // Create a demo tenant and add the super admin as a member, set as current tenant
        $demoName = env('DEMO_TENANT_NAME', 'Demo Tenant');
        $demoSlug = env('DEMO_TENANT_SLUG', 'demo-tenant');

        $demoTenant = \App\Models\Tenant::firstOrCreate([
            'slug' => $demoSlug,
        ], [
            'name' => $demoName,
            'owner_id' => $user->id,
            'settings' => [],
        ]);

        // Ensure super admin is a member (owner) so switching works and demos show member-specific info
        if (!$demoTenant->hasMember($user)) {
            $demoTenant->addMember($user, \App\Models\TenantUser::ROLE_OWNER);
        }

        // Set as current tenant for the super admin
        if ($user->current_tenant_id !== $demoTenant->id) {
            $user->update(['current_tenant_id' => $demoTenant->id]);
        }
    }
}
