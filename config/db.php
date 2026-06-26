<?php declare(strict_types=1);

return [
    'db' => [
        'prefix' => '',

        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'concept_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',

        'log_enabled' => false,
        'log_path' => 'query',
        'log_max_files' => 7,
    ],
];
