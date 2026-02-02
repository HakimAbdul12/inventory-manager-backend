<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Generation Configuration
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Default Steps
    |--------------------------------------------------------------------------
    |
    | The default steps in the inventory generation pipeline.
    | Order matters - steps are executed sequentially.
    |
    */

    'steps' => [
        'text_generation' => [
            'name' => 'Text Generation',
            'description' => 'Generate inventory content using AI',
            'order' => 1,
            'timeout' => 60,
            'retries' => 3,
        ],
        'image_generation' => [
            'name' => 'Image Generation',
            'description' => 'Generate product images using AI',
            'order' => 2,
            'timeout' => 120,
            'retries' => 2,
            'optional' => true,
        ],
        'finalization' => [
            'name' => 'Finalization',
            'description' => 'Finalize and save inventory item',
            'order' => 3,
            'timeout' => 30,
            'retries' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Generation Settings
    |--------------------------------------------------------------------------
    */

    'images' => [
        'default_count' => 3,
        'max_count' => 10,
        'styles' => ['professional', 'lifestyle', 'studio'],
        'default_style' => 'professional',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'connection' => env('INVENTORY_QUEUE_CONNECTION', 'database'),
        'name' => env('INVENTORY_QUEUE_NAME', 'inventory'),
    ],
];
