<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\Stack\ConceptStack;
use League\Container\Container;

/**
 * Builds ConceptStack and registers its providers.
 */
final class ApplicationStackBootstrap
{
    public function boot(Container $container): void
    {
        $rootPath = __DIR__ . '/../../../';
        $debug = false;
        $stack = ConceptStack::create();

        $stack->addHttp()
            ->withTypedRouteParameters()
            ->setRoutes([$rootPath . 'routes/web.php']);

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
