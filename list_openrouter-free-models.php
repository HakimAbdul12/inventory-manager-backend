<?php

use App\Services\OpenRouterClient;

$client = app(OpenRouterClient::class);
$models = $client->getModels();

echo "Available Free Models:\n";
foreach ($models as $model) {
    if (str_contains($model['id'], ':free')) {
        echo $model['id'] . "\n";
    }
}
