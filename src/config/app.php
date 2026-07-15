<?php

return [
    'name' => 'Laravel CLI Pack',
    'version' => '1.0.0',
    'file_auth_basic_protection' => env('CLIPACK_AUTH_BASIC_FILE', 'auth_basic_protection'),

    'auth_basic' => [
        'enabled' => filter_var(env('CLIPACK_AUTH_BASIC_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'user' => env('CLIPACK_AUTH_USER', 'user'),
        'password' => env('CLIPACK_AUTH_PASSWORD', 'secretPassword'),
    ],

    'run_php' => [
        'enabled' => filter_var(env('CLIPACK_RUN_PHP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'allowed_path' => storage_path('app/clipack-scripts'),
    ],

    /**
     * Optional hostnames allowed by JsonGuard middleware.
     * CLIPACK_ALLOWED_HOSTS can also provide a comma-separated list.
     *
     * @var array<int, string>
     */
    'allowed_hosts' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('CLIPACK_ALLOWED_HOSTS', ''))
    ))),

    'mysql_binary' => env('CLIPACK_MYSQL_BINARY', 'mysql'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'user' => [
        'model' => env('CLIPACK_USER_MODEL', 'App\\Models\\User'),
    ],

    'disk' => [
        'log_file' => env('CLIPACK_DISK_LOG_FILE', 'clipack-disk-space.log'),
    ],

    'log_rotate' => [
        'path' => env('CLIPACK_LOG_ROTATE_PATH'),
        'archive' => env('CLIPACK_LOG_ROTATE_ARCHIVE'),
        'days' => (int) env('CLIPACK_LOG_ROTATE_DAYS', 60),
        'create_missing' => filter_var(env('CLIPACK_LOG_ROTATE_CREATE_MISSING', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'clear_caches' => [
        'extra_logs_dir' => env('CLIPACK_EXTRA_LOGS_DIR'),
    ],
];
