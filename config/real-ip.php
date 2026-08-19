<?php

return [
    'headers' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('REAL_IP_HEADERS', 'CF-Connecting-IP,True-Client-IP,X-Forwarded-For,X-Real-IP'))
    ))),

    'server_keys' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('REAL_IP_SERVER_KEYS', 'REMOTE_ADDR'))
    ))),

    'forwarded_for_index' => (int) env('REAL_IP_FORWARDED_FOR_INDEX', -1),

    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('TRUSTED_PROXIES', ''))
    ))),
];
