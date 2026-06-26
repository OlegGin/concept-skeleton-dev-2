<?php declare(strict_types=1);

/**
 * Static ACL definitions used when acl.storage !== 'database'.
 * Ignored when rules are loaded from the database (default).
 */
return [
    'acl' => [
        'definitions' => [
            'roles' => [
                'guest' => null,
                'user' => 'guest',
                'editor' => 'user',
                'manager' => 'editor',
                'admin' => null,
            ],

            'resources' => [
                'cabinet',
                'admin',
                'admin.acl',
                'admin.users',
                'admin.settings',
                'admin.content',
            ],

            'allow' => [
                ['role' => 'guest', 'resource' => 'cabinet', 'privilege' => 'view'],
                ['role' => 'user', 'resource' => 'cabinet'],
                ['role' => 'editor', 'resource' => 'admin'],
                ['role' => 'editor', 'resource' => 'admin.content'],
                ['role' => 'manager', 'resource' => 'admin'],
                ['role' => 'manager', 'resource' => 'admin.settings'],
                ['role' => 'manager', 'resource' => 'admin.content'],
                ['role' => 'manager', 'resource' => 'admin.users'],
                ['role' => 'admin', 'resource' => 'admin'],
            ],

            'deny' => [
                ['role' => 'guest', 'resource' => 'admin'],
                ['role' => 'user', 'resource' => 'admin'],
                ['role' => 'editor', 'resource' => 'admin.users'],
                ['role' => 'editor', 'resource' => 'admin.settings'],
                ['role' => 'editor', 'resource' => 'admin.acl'],
                ['role' => 'manager', 'resource' => 'admin.acl'],
            ],
        ],
    ],
];
