<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

$user = User::where('email', 'hakim.abdul.tech@gmail.com')->first();

if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User found: {$user->id} - {$user->name} - {$user->email}\n";

// Check if tenant already exists for this user
$existingTenant = $user->tenants()->first();
if ($existingTenant) {
    $tenant = $existingTenant;
    echo "Tenant already exists: {$tenant->id} - {$tenant->name}\n";
} else {
    // Create tenant
    $tenantName = $user->company_name ?: "{$user->name}'s Workspace";
    $tenant = Tenant::create([
        'name' => $tenantName,
        'slug' => Str::slug($tenantName),
        'owner_id' => $user->id,
    ]);

    // Attach user as owner
    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    echo "Tenant created: {$tenant->id} - {$tenant->name}\n";
}

// Set as current tenant
$user->update(['current_tenant_id' => $tenant->id]);
echo "Set as current tenant for user.\n";

// Transfer user banner to tenant if tenant doesn't have one
if (!$tenant->banner_image && $user->banner_image) {
    $tenant->update(['banner_image' => $user->banner_image]);
    echo "Transferred user banner to tenant: {$user->banner_image}\n";
} else {
    echo "No banner transfer needed (tenant already has one or user has none).\n";
}
echo "\n";

// --- Assign tenant_id to existing records ---

echo "--- Assigning tenant_id to records ---\n";

// Inventory Items (via user_id)
$count = DB::table('inventory_items')
    ->whereNull('tenant_id')
    ->where('user_id', $user->id)
    ->update(['tenant_id' => $tenant->id]);
echo "inventory_items updated: {$count}\n";

// Inventory Processes
$count = DB::table('inventory_processes')
    ->whereNull('tenant_id')
    ->where('user_id', $user->id)
    ->update(['tenant_id' => $tenant->id]);
echo "inventory_processes updated: {$count}\n";

// Imports
$count = DB::table('imports')
    ->whereNull('tenant_id')
    ->where('user_id', $user->id)
    ->update(['tenant_id' => $tenant->id]);
echo "imports updated: {$count}\n";

// Transfers (sender or recipient)
$count = DB::table('transfers')
    ->whereNull('tenant_id')
    ->where(function ($q) use ($user) {
        $q->where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id);
    })
    ->update(['tenant_id' => $tenant->id]);
echo "transfers updated: {$count}\n";

// Virtual Showrooms
$count = DB::table('virtual_showrooms')
    ->whereNull('tenant_id')
    ->where('user_id', $user->id)
    ->update(['tenant_id' => $tenant->id]);
echo "virtual_showrooms updated: {$count}\n";

echo "\nDone! All existing records assigned to tenant: {$tenant->name} ({$tenant->id})\n";
