<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin Role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'admin']);

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), // Ensure you change this in production
                'dealer_code' => 'ADMIN001',
                'company_name' => 'Admin Company',
            ]
        );

        // Assign Role
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($role);
        }
    }
}
