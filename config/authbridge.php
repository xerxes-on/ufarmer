<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of your application's User model.
    | The model must implement Xerxes\AuthBridge\Contracts\AuthBridgeUserContract,
    | or simply use the HasAuthBridgeColumns trait which provides a default
    | config-driven implementation.
    |
    */
    'user_model' => env('AUTHBRIDGE_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Connection names for the authbridge database (central auth) and local
    | database. The authbridge connection is used to query tokens and remote
    | users from the central authentication service.
    |
    */
    'connections' => [
        'authbridge' => env('AUTHBRIDGE_CONNECTION', 'authbridge'),
        'local' => env('AUTHBRIDGE_LOCAL_CONNECTION', null), // null uses default
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for token validation. The token table is the personal access
    | tokens table in the authbridge database. The hash algorithm should
    | match what was used to create the tokens (typically sha256).
    |
    */
    'token' => [
        'table' => env('AUTHBRIDGE_TOKEN_TABLE', 'personal_access_tokens'),
        'hash_algo' => env('AUTHBRIDGE_TOKEN_HASH_ALGO', 'sha256'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Super User (Remote User) Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for querying remote users from the central auth database.
    |
    */
    'super_user' => [
        'table' => env('AUTHBRIDGE_SUPER_USER_TABLE', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for application/organization resolution. The header is
    | used to identify which application the request is coming from.
    |
    | You can define application aliases in the 'aliases' array, or set
    | 'use_database' to true to query applications from a database table.
    |
    */
    'application' => [
        'header' => env('AUTHBRIDGE_APP_HEADER', 'x-application-alias'),
        'default_alias' => env('AUTHBRIDGE_DEFAULT_ALIAS', 'admin_panel'),
        'use_database' => env('AUTHBRIDGE_APP_USE_DATABASE', false),
        'table' => env('AUTHBRIDGE_APP_TABLE', 'applications'),
        'aliases' => [
            // Define your applications here, or load from database
            // 'farmer' => [
            //     'id' => 1,
            //     'name' => 'Farmer App',
            //     'organization_id' => 100,
            // ],
            // 'marketplace' => [
            //     'id' => 2,
            //     'name' => 'Marketplace',
            //     'organization_id' => 100,
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Organization Pivot Table
    |--------------------------------------------------------------------------
    |
    | Configuration for the pivot table that links users to organizations.
    | This is used to check if a remote user belongs to an application's
    | organization and to get/set the local entity_id.
    |
    */
    'organization_pivot' => [
        'table' => env('AUTHBRIDGE_ORG_PIVOT_TABLE', 'application_users'),
        'user_column' => env('AUTHBRIDGE_ORG_PIVOT_USER_COL', 'user_id'),
        'organization_column' => env('AUTHBRIDGE_ORG_PIVOT_ORG_COL', 'application_id'),
        'entity_id_column' => env('AUTHBRIDGE_ORG_PIVOT_ENTITY_ID_COL', 'entity_id'),
        'entity_type_column' => env('AUTHBRIDGE_ORG_PIVOT_ENTITY_TYPE_COL', 'entity_type'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone Conflict Resolution
    |--------------------------------------------------------------------------
    |
    | When a user is found by phone but has a different auth_id:
    | 'update' - Update the auth_id to match (default, assumes same person)
    | 'error'  - Throw an exception (409 Conflict)
    |
    */
    'phone_conflict_strategy' => env('AUTHBRIDGE_PHONE_CONFLICT', 'update'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The locales to include in error message responses. These should match
    | the translation files available in resources/lang.
    |
    */
    'locales' => ['en', 'ru', 'uz'],

    /*
    |--------------------------------------------------------------------------
    | User Columns
    |--------------------------------------------------------------------------
    |
    | Column names in your local users table for AuthBridge-managed fields.
    | Each column is independently opt-in — set to `false` / `null` / `''`
    | to tell the package your local schema does not have that column.
    |
    | `name` is synced remote-wins from the central auth database on every
    | authentication. Apps whose users table has no `name` column must set
    | `AUTHBRIDGE_COL_NAME=false` in their environment, otherwise syncing
    | will throw a TypeError on the first bearer-auth request.
    |
    */
    'columns' => [
        'auth_id' => env('AUTHBRIDGE_COL_AUTH_ID', 'auth_id'),
        'phone' => env('AUTHBRIDGE_COL_PHONE', 'phone'),
        'name' => env('AUTHBRIDGE_COL_NAME', 'name'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Enable caching to reduce load on the auth database. Cached data includes
    | tokens and remote users. Set TTL to 0 to disable caching.
    |
    | Note: When a token is revoked, it may remain valid for up to TTL seconds.
    | For immediate revocation, use event-driven cache invalidation or set a
    | lower TTL value.
    |
    */
    'cache' => [
        'enabled' => env('AUTHBRIDGE_CACHE_ENABLED', true),
        'store' => env('AUTHBRIDGE_CACHE_STORE', null), // null uses default cache store
        'prefix' => env('AUTHBRIDGE_CACHE_PREFIX', 'authbridge'),
        'ttl' => [
            'token' => env('AUTHBRIDGE_CACHE_TTL_TOKEN', 60),                    // seconds
            'remote_user' => env('AUTHBRIDGE_CACHE_TTL_USER', 120),              // seconds
            'pivot' => env('AUTHBRIDGE_CACHE_TTL_PIVOT', 120),                   // seconds
            'user_applications' => env('AUTHBRIDGE_CACHE_TTL_USER_APPS', 120),   // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SSO Client Configuration
    |--------------------------------------------------------------------------
    |
    | Reusable OAuth client settings for redirecting browser users to the
    | central ufarm-auth service, exchanging the returned code for a token,
    | and authenticating the local user through this package.
    |
    */
    'sso' => [
        'enabled' => env('AUTHBRIDGE_SSO_ENABLED', false),
        'base_url' => env('AUTHBRIDGE_SSO_BASE_URL'),
        'authorize_path' => env('AUTHBRIDGE_SSO_AUTHORIZE_PATH', '/oauth/authorize'),
        'token_path' => env('AUTHBRIDGE_SSO_TOKEN_PATH', '/api/v1/oauth/token'),
        'redirect_uri' => env('AUTHBRIDGE_SSO_REDIRECT_URI'),
        'client_id' => env('AUTHBRIDGE_SSO_CLIENT_ID'),
        'client_secret' => env('AUTHBRIDGE_SSO_CLIENT_SECRET'),
        'guard' => env('AUTHBRIDGE_SSO_GUARD', 'web'),
    ],
];
