<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = 'abdulhakimaben@gmail.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User not found.\n";
    exit(1);
}

$token = $user->createToken('Dealer Website Token', ['inventory:read']);

echo "API_KEY=" . $token->plainTextToken . "\n";
