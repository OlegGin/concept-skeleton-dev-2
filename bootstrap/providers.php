<?php declare(strict_types=1);

use Concept\App\Providers\TestServiceProvider;
use Concept\Core\App;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\Casting\CastingServiceProvider;
use Concept\Extensions\Casting\Contracts\CasterInterface;
use Concept\Extensions\Casting\Routing\TypedRouteParameterArgumentResolver;
use Concept\Extensions\Http\HttpServiceProvider;
use League\Container\Container;

/** @var Container $container */
return [
    function () {
        return new CastingServiceProvider(
            cacheDirectory: dirname(__DIR__) . '/storage/cache/valinor',
            debug: filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
        );
    },
    function () use ($container) {
        return new CoreHttpServiceProvider(
            [
                dirname(__DIR__) . '/routes/web.php',
            ],
            resolvers: [
                // FormRequestArgumentResolver — first, when extension is wired
                new ServerRequestArgumentResolver(),
                new TypedRouteParameterArgumentResolver($container->get(CasterInterface::class)),
                new RouteParameterArgumentResolver(),
            ],
        );
    },
    function () {
        return new HttpServiceProvider();
    },
    function () use ($container) {
        return new TestServiceProvider($container->get(App::class));
    },
];
