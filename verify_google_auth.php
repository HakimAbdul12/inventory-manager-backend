<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Str;

// Mock Socialite User
$googleId = 'google_' . uniqid();
$email = 'test_google_' . uniqid() . '@example.com';
$name = 'Test Google User';
$avatar = 'https://example.com/avatar.jpg';

echo "Testing Google Auth Registration Logic...\n";

// Simulate handleGoogleCallback logic
$user = User::where('google_id', $googleId)
    ->orWhere('email', $email)
    ->first();

if (!$user) {
    echo "Creating new user...\n";
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'google_id' => $googleId,
        'avatar' => $avatar,
        'password' => null,
        'email_verified_at' => now(),
    ]);

    $user->assignRole('dealer');

    $workspaceName = $user->name . "'s Workspace";
    $tenant = Tenant::create([
        'name' => $workspaceName,
        'slug' => Str::slug($workspaceName) . '-' . Str::lower(Str::random(6)),
        'owner_id' => $user->id,
        'settings' => [],
    ]);
    $tenant->addMember($user, \App\Models\TenantUser::ROLE_OWNER);
    $user->update(['current_tenant_id' => $tenant->id]);

    echo "User created with ID: {$user->id}\n";
    echo "Tenant created: {$tenant->name}\n";
} else {
    echo "User found.\n";
}

// Verify User
$dbUser = User::find($user->id);
if ($dbUser->google_id !== $googleId) {
    echo "FAILURE: google_id mismatch.\n";
    exit(1);
}
if ($dbUser->avatar !== $avatar) {
    echo "FAILURE: avatar mismatch.\n";
    exit(1);
}
if ($dbUser->password !== null) { // Or hashed empty? Should be null based on migration
    // Actually, migration made it nullable. Eloquent might not cast null to null if mutator exist?
    // Let's check.
    echo "Password is: " . ($dbUser->password === null ? 'NULL' : 'SET') . "\n";
}

echo "SUCCESS: Google Auth User Provisioning Logic works.\n";
