<?php declare(strict_types=1);

use Database\Seeders\PageSeeder;

return [
    'db' => [
        'prefix' => '',

        'driver' => 'mysql',
        'host' => 'concept_skeleton_dev_db_2',
        'port' => 3306,
        'database' => 'concept_skeleton_dev_db_2',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',

        'log_enabled' => false,
        'log_file' => 'query.log',
        'log_max_files' => 7,
    ],
    'migrations' => [
        'table' => 'migrations',
        'paths' => [
            'database/migrations',
        ],
    ],
    'seeders' => [
        'list' => [
            PageSeeder::class,
        ],
    ],
];
