<?php

declare(strict_types=1);

return [
    'name' => 'PlantScanner',

    'image' => [
        'max_width' => (int) env('PLANT_SCANNER_IMAGE_MAX_WIDTH', 1024),
        'max_height' => (int) env('PLANT_SCANNER_IMAGE_MAX_HEIGHT', 1024),
        'quality' => (int) env('PLANT_SCANNER_IMAGE_QUALITY', 85),
        'optimized_disk' => env('PLANT_SCANNER_IMAGE_OPTIMIZED_DISK', 'public'),
    ],

    'ai' => [
        'default_provider' => env('PLANT_SCANNER_AI_PROVIDER', 'openrouter'),
        'model' => env('PLANT_SCANNER_AI_MODEL', 'x-ai/grok-4-fast'),
        'timeout' => (int) env('PLANT_SCANNER_AI_TIMEOUT', 60),
        'max_retries' => (int) env('PLANT_SCANNER_AI_MAX_RETRIES', 3),
        'language' => env('PLANT_SCANNER_AI_LANGUAGE', 'en'),
        'php_execution_time' => (int) env('PLANT_SCANNER_AI_PHP_EXECUTION_TIME', 120),
    ],
];
