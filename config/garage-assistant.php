<?php

return [
    'enabled' => env('GARAGE_ASSISTANT_ENABLED', false),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.7-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),
        'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 5),
    ],

    'rate_limit' => [
        'attempts' => (int) env('GARAGE_ASSISTANT_RATE_LIMIT', 20),
        'decay_seconds' => 60,
    ],

    'proposal_ttl_seconds' => 600,
];
