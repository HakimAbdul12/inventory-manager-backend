<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OpenRouterClient;

$client = app(OpenRouterClient::class);

try {
    $models = $client->getModels();
    
    $freeModels = array_filter($models, function($model) {
        return str_contains($model['id'], ':free');
    });

    echo "Free models:\n";
    foreach ($freeModels as $model) {
        echo "- {$model['id']} (Pricing: " . json_encode($model['pricing'] ?? []) . ")\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
