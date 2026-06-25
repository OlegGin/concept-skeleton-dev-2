<?php declare(strict_types=1);

use Concept\App\View\Twig\AppExtension;

return [
    'view' => [
        'paths' => [
            'app' => 'resources/views',
        ],
        'extensions' => [
            AppExtension::class,
        ],
        'contexts' => [],
    ],
];
