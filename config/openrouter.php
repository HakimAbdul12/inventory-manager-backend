<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the OpenRouter AI API used for generating inventory
    | content. Uses free tier models by default.
    |
    */

    'api_key' => env('OPENROUTER_API_KEY'),

    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),

    'model' => env('OPENROUTER_MODEL', 'liquid/lfm-2.5-1.2b-thinking:free'),

    'vision_model' => env('OPENROUTER_VISION_MODEL', 'nvidia/nemotron-nano-12b-v2-vl:free'),

    'image_model' => env('OPENROUTER_IMAGE_MODEL', 'google/gemini-3.1-flash-image-preview'),

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    */

    'timeout' => env('OPENROUTER_TIMEOUT', 60),

    'image_timeout' => env('OPENROUTER_IMAGE_TIMEOUT', 120), // Longer timeout for image generation

    'max_retries' => env('OPENROUTER_MAX_RETRIES', 3),

    'retry_delay' => env('OPENROUTER_RETRY_DELAY', 1000), // milliseconds

    /*
    |--------------------------------------------------------------------------
    | Token Limits
    |--------------------------------------------------------------------------
    */

    'max_tokens' => env('OPENROUTER_MAX_TOKENS', 2000),

    'temperature' => env('OPENROUTER_TEMPERATURE', 0.7),
];
