<?php

declare(strict_types=1);

return [
    'rabbitmq' => [
        'queue' => env('PREMIUM_RABBITMQ_QUEUE', 'premium.subscriptions'),
    ],

    'jobs' => [
        'expiry_check_at' => env('PREMIUM_EXPIRY_CHECK_AT', '01:00'),
    ],
];
