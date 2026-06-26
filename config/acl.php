<?php declare(strict_types=1);

use Concept\Components\Acl\RoleResolvers\SessionRoleResolver;

return [
    'acl' => [
        'storage' => 'database',
        'default_role' => 'guest',
        'default_user_role' => 'user',
        'role_resolver' => SessionRoleResolver::class,
        'redirect_route_name' => 'admin.dashboard',
    ],
];
