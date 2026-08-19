<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'yandex' => [
        'maps_api_key' => env('YANDEX_MAPS_API_KEY', ''),
    ],

    'ufarm_auth' => [
        'base_url' => env('UFARM_AUTH_BASE_URL', 'https://auth.prod.ufarmer.uz'),
        'timeout' => (int) env('UFARM_AUTH_TIMEOUT', 15),
    ],

    'authbridge' => [
        'jsonrpc' => [
            'endpoint' => env('JSONRPC_ENDPOINT'),
            'username' => env('JSONRPC_USERNAME'),
            'password' => env('JSONRPC_PASSWORD'),
        ],
        // Application alias employees must be attached to so they satisfy
        // the "UFarm Admin Panel" OAuth client's required_applications gate
        // on login (see SsoController::APPLICATION_ALIAS).
        'employee_application_alias' => env('SSO_EMPLOYEE_APPLICATION_ALIAS', 'admin_panel'),
        // Alias imported service workers are attached to. `in_service` is
        // ufarm-auth's "Service Worker" application (id 13) and the only one
        // of the two service aliases with a Role mapping in its config/roles.php
        // — a user attached solely to `service-owner` resolves to no role at all.
        'worker_application_alias' => env('SSO_WORKER_APPLICATION_ALIAS', 'in_service'),
    ],

    /*
    | OpenRouter powers the AI worker import (UFARM-2644). The import stays
    | off until `worker_import_enabled` is set AND a key is present, so an
    | unconfigured environment hides the feature rather than failing at the
    | first request. Mirrors ufarm-marketplace's product-import block.
    */
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'openai/gpt-4.1-mini'),
        'translation_retry_model' => env('OPENROUTER_TRANSLATION_RETRY_MODEL', 'openai/gpt-4.1-mini'),
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OPENROUTER_FALLBACK_MODELS', ''))
        ))),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 120),
        'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 10),
        'referer' => env('OPENROUTER_REFERER', env('APP_URL')),
        'title' => env('OPENROUTER_TITLE', 'UFarm Admin'),
        'max_input_chars' => (int) env('OPENROUTER_MAX_INPUT_CHARS', 60000),
        // Headroom for a full chunk of records. Set to 0 to leave the choice
        // to the provider; a value too small now fails loudly rather than
        // silently returning half an answer.
        'max_output_tokens' => (int) env('OPENROUTER_MAX_OUTPUT_TOKENS', 16000),
        'taxonomy_resolution' => (bool) env('OPENROUTER_TAXONOMY_RESOLUTION', true),
        // Create a service category when a sheet names a service the catalog
        // has no word for. Created inactive, for an admin to review.
        'category_creation' => (bool) env('OPENROUTER_CATEGORY_CREATION', true),
        'max_created_categories' => (int) env('OPENROUTER_MAX_CREATED_CATEGORIES', 10),
        'max_resolution_categories' => (int) env('OPENROUTER_MAX_RESOLUTION_CATEGORIES', 200),
        'max_resolution_names' => (int) env('OPENROUTER_MAX_RESOLUTION_NAMES', 40),
        'category_match_threshold' => (float) env('OPENROUTER_CATEGORY_MATCH_THRESHOLD', 0.8),
        'worker_import_enabled' => (bool) env('WORKER_AI_IMPORT_ENABLED', false),
        'worker_import_max_upload_kb' => (int) env('WORKER_AI_IMPORT_MAX_UPLOAD_KB', 10240),
    ],

    'telegram' => [
        // Per-channel bot configuration. Each notifier reads its own sub-key so
        // we can rotate bots/channels independently.
        'uzgidromet' => [
            'bot_token' => env('TG_UZGIDROMET_BOT_TOKEN'),
            'channel_id' => env('TG_UZGIDROMET_CHANNEL_ID'),
        ],
    ],

    /*
    | Exchange that ufarm-api publishes farmer/crop model changes on. Crop
    | edits made in this panel are published to the same exchange with the
    | same routing keys, so existing consumers pick them up unchanged.
    | Must match ufarm-api's RABBITMQ_FARMER_EXCHANGE.
    */
    'farmer_events' => [
        'exchange' => env('RABBITMQ_FARMER_EXCHANGE', 'ufarm.events'),
    ],

];
