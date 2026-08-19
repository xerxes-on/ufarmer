<?php

declare(strict_types=1);

return [
    'rpc_endpoint' => env('BILLING_RPC_ENDPOINT'),
    'rpc_username' => env('BILLING_RPC_USERNAME'),
    'rpc_password' => env('BILLING_RPC_PASSWORD'),
    'application_alias' => env('BILLING_APPLICATION_ALIAS', 'erp'),

    'webhook_prefix' => env('BILLING_WEBHOOK_PREFIX'),
    'public_key_path' => env('BILLING_PUBLIC_KEY_PATH'),

    'order_reflection_alias' => env('BILLING_ORDER_REFLECTION_ALIAS'),
    'balance_reflection_alias' => env('BILLING_BALANCE_REFLECTION_ALIAS'),
    'autopayment_reflection_alias' => env('BILLING_AUTOPAYMENT_REFLECTION_ALIAS'),
];
