<?php

return [
    'name' => env('APP_NAME', 'Phone Distribution System'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_key' => env('APP_PREVIOUS_KEY'),
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'currency_code' => env('APP_CURRENCY_CODE', 'TZS'),
    'currency_symbol' => env('APP_CURRENCY_SYMBOL', 'TSh'),
    'currency_name' => env('APP_CURRENCY_NAME', 'Tanzanian Shilling'),
];
