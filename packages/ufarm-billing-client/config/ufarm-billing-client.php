<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Entity ID Separator
    |--------------------------------------------------------------------------
    |
    | The separator used in entity IDs to distinguish service type from entity ID.
    | Example: "agronom:123" where ":" is the separator.
    |
    */
    'entity_separator' => ':',

    /*
    |--------------------------------------------------------------------------
    | Service Type Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to cache service types in seconds. Set to 0 to disable caching.
    |
    */
    'cache_ttl' => (int) env('UFARM_BILLING_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code to use when no currency is specified.
    |
    */
    'default_currency' => env('UFARM_BILLING_DEFAULT_CURRENCY', 'UZS'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for debugging purposes.
    |
    */
    'logging' => [
        'enabled' => (bool) env('UFARM_BILLING_LOGGING', true),
        'channel' => env('UFARM_BILLING_LOG_CHANNEL', 'stack'),
    ],
];
