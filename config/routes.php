<?php declare(strict_types=1);

use Concept\Components\Acl\Authorization\AclRouteAuthorization;

return [
    'routes' => [
        'interceptors' => [
            AclRouteAuthorization::class,
        ],
        'list' => [
            'routes/web.php',
            'routes/api.php',
        ],
    ],
];
