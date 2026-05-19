<?php

return [
    'domain' => env('CARESOFT_DOMAIN', ''),
    'api_token' => env('CARESOFT_API_TOKEN', ''),
    'api_host' => env('CARESOFT_API_HOST', 'https://api.caresoft.vn'),
    'api_host_backup' => env('CARESOFT_API_HOST_BACKUP', 'https://api2.caresoft.vn'),

    'per_page' => 500,
    'rate_limit_delay_ms' => 200,
    'max_retries' => 3,
    'retry_delay_ms' => 1000,
    'timeout' => 30,

    'service_types' => [
        1 => 'Gọi vào',
        2 => 'Live chat',
        3 => 'Email',
        4 => 'Facebook',
        6 => 'Miss chat',
        7 => 'Voicemail',
        8 => 'Inbox Facebook',
        9 => 'Api',
        10 => 'Zalo',
        11 => 'Voice out',
        12 => 'Ivr',
        13 => 'Ticket form',
    ],
];
