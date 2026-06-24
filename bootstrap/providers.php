<?php declare(strict_types=1);

use Concept\App\Providers\TestServiceProvider;
use Concept\Core\App;
use Concept\Core\Providers\Http\HttpServiceProvider;
use League\Container\Container;

/** @var Container $container */
return [
    function() use ($container) {
        return new HttpServiceProvider(
            [
                dirname(__DIR__) . '/routes/web.php',
            ]
        );
    },
    function() use ($container) {
        return new TestServiceProvider($container->get(App::class));
    },
];
