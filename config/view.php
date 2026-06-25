<?php declare(strict_types=1);

return [
    'view' => [
        'paths' => [
            'app' => 'resources/views',
        ],
        'extensions' => [
            \Concept\App\View\Twig\AppExtension::class,
        ],
        'contexts' => [],
    ],
];
