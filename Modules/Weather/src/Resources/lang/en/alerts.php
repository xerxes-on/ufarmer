<?php

declare(strict_types=1);

return [
    'extremely_cold' => [
        'title' => 'Read Instructions',
        'message' => 'Dangerously cold weather detected. Temperature is :diff°C below recommended. Please take immediate action to protect your crops.',
    ],
    'very_cold' => [
        'title' => 'Cold Weather Alert',
        'message' => 'Temperature is :diff°C below recommended range. Consider protective measures for your crops.',
    ],
    'cold' => [
        'title' => 'Cool Weather Notice',
        'message' => 'Temperature is :diff°C below optimal. Monitor crop conditions closely.',
    ],
    'extremely_hot' => [
        'title' => 'Read Instructions',
        'message' => 'Extremely hot weather detected. Temperature is :diff°C above recommended. Please take immediate action to protect your crops.',
    ],
    'very_hot' => [
        'title' => 'Hot Weather Alert',
        'message' => 'Temperature is :diff°C above recommended range. Consider cooling measures for your crops.',
    ],
    'hot' => [
        'title' => 'Warm Weather Notice',
        'message' => 'Temperature is :diff°C above optimal. Monitor crop conditions closely.',
    ],
    'optimal' => [
        'title' => 'Optimal Conditions',
        'message' => 'Current temperature is within the recommended range for this crop.',
    ],
    'no_recommendation' => [
        'title' => 'No Temperature Data',
        'message' => 'Temperature recommendations are not configured for this crop.',
    ],
];
