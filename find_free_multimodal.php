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
        $pricing = $model['pricing'] ?? [];
        $isFree = ($pricing['prompt'] ?? null) === "0" && ($pricing['completion'] ?? null) === "0";
        
        if ($isFree) {
            // Check if it supports images in description or architecture
            $desc = strtolower($model['description'] ?? '');
            $name = strtolower($model['name'] ?? '');
            if (str_contains($desc, 'image') || str_contains($name, 'image') || str_contains($desc, 'vision')) {
                echo "Free Multimodal Model: " . $model['id'] . "\n";
                echo "Description: " . $model['description'] . "\n\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
