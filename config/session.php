<?php declare(strict_types=1);

return [
    'session' => [
        'driver' => 'file',

        'cookie' => [
            'lifetime' => 0,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'domain' => '',
            'samesite' => 'Lax',
        ],

        'options' => [
            'use_only_cookies' => true,
            'use_strict_mode' => true,
        ],

        'file' => [
            'path' => null,
        ],

        'redis' => [
            'url' => 'redis://127.0.0.1:6379',
            'prefix' => 'sess_',
        ],

        'pdo' => [
            'dsn' => '',
            'table' => 'sessions',
        ],
    ],
];
