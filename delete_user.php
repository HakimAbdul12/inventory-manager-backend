<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'techoneit.channel@gmail.com';
$user = User::where('email', $email)->first();

if ($user) {
    if ($user->delete()) {
        echo "User {$email} deleted successfully.\n";
    } else {
        echo "Failed to delete user {$email}.\n";
    }
} else {
    echo "User {$email} not found.\n";
}
