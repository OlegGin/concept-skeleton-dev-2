<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Http\Error\Contracts\HttpErrorRendererInterface;
use Concept\App\Http\Error\JsonHttpErrorRenderer;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * API profile: JSON error responses only (no View / PrettyPage).
 */
final class JsonErrorHandlingLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->getContainer()->addServiceProvider(new ErrorHandlingLayerProvider(
            httpErrorRendererFactory: fn(DefinitionContainerInterface $container): HttpErrorRendererInterface => new JsonHttpErrorRenderer(
                responseFactory: ContainerDependency::get($container, ResponseFactoryInterface::class),
            ),
        ));
    }
}
