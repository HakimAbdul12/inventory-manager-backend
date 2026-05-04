<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OpenRouterClient;

$client = app(OpenRouterClient::class);

try {
    $models = $client->getModels();
    foreach ($models as $model) {
        if ($model['id'] === 'google/gemini-3.1-flash-image-preview') {
            echo "Model: " . $model['id'] . "\n";
            echo "Pricing: " . json_encode($model['pricing']) . "\n";
            break;
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
