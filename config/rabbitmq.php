<?php

declare(strict_types=1);

return [
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASS', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'event-consumers' => [
        [
            'exchange' => 'marketplace.events',
            'exchange_type' => 'topic',
            'event' => '\App\Events\MarketProductProposalReceivedEvent',
            'routing_key' => 'marketplace.proposal.*',
            'map_into' => '\App\Events\MarketProductProposalReceivedEvent',
            'queue' => 'ufarm_admin.marketplace_proposals',
        ],
        [
            'exchange' => 'ufarm.events',
            'exchange_type' => 'topic',
            'event' => null,
            'routing_key' => 'admin.notification.requested',
            'handler' => [\Modules\Core\Listeners\DispatchAdminNotification::class, 'handleRaw'],
            'map_into' => null,
            'queue' => 'ufarm-admin.notifications',
        ],
    ],

    'event-consumer-mode' => 'kind-sync',

    'log-channel' => env('RABBITMQ_LOG_CHANNEL', env('LOG_CHANNEL', 'stack')),

    'outbox' => [
        'enabled' => env('RABBITMQ_OUTBOX_ENABLED', true),
        'connection' => env('RABBITMQ_OUTBOX_CONNECTION', 'pgsql'),
    ],
];
