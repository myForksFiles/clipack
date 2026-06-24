<?php

return [
    'name' => 'Laravel CLI Pack',
    'version' => '1.0.0',
    'file_auth_basic_protection' => 'auth_basic_protection',

    'auth_basic' => [
        'enabled' => env('CLIPACK_AUTH_BASIC_ENABLED', false),
        'user' => env('CLIPACK_AUTH_USER', 'user'),
        'password' => env('CLIPACK_AUTH_PASSWORD', 'secretPassword'),
    ],

    'run_php' => [
        'enabled' => env('CLIPACK_RUN_PHP_ENABLED', false),
        'allowed_path' => storage_path('app/clipack-scripts'),
    ],
];
