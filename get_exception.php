<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$job = \DB::table('failed_jobs')->latest('failed_at')->first();
if ($job) {
    echo "Exception Class: \n";
    $lines = explode("\n", $job->exception);
    echo implode("\n", array_slice($lines, 0, 10)) . "\n";
} else {
    echo "No failed jobs.\n";
}
