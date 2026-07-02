<?php declare(strict_types=1);

use Concept\App\View\Twig\TwigAppExtension;

return [
    'view' => [
        'paths' => [
            'frontend' => '/resources/views/frontend',
            'dashboard' => '/resources/views/dashboard',
        ],
        'extensions' => [
            TwigAppExtension::class,
        ],
        'route_namespace' => [
        ],
    ],
];
