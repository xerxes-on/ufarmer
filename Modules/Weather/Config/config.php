<?php

declare(strict_types=1);

return [
    'default' => env('WEATHER_DRIVER', 'openweather'),

    'drivers' => [
        'openweather' => [
            'api_key' => env('OPENWEATHER_API_KEY'),
            'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5/onecall'),
        ],
    ],
];
