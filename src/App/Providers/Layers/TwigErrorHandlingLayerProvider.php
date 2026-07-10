<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\PathName;
use Concept\App\Http\Error\Contracts\ExceptionReporterInterface;
use Concept\App\Http\Error\Contracts\HttpErrorRendererInterface;
use Concept\App\Http\Error\TwigHttpErrorRenderer;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\View\Support\ViewRouteNamespaceResolver;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Whoops\Handler\PrettyPageHandler;

/**
 * Full / admin web: Twig (HTML) error pages + PrettyPage in debug.
 */
final class TwigErrorHandlingLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
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
            httpErrorRendererFactory: function(DefinitionContainerInterface $container): HttpErrorRendererInterface {
                $pathManager = ContainerDependency::get($container, PathManager::class);

                return new TwigHttpErrorRenderer(
                    responseFactory: ContainerDependency::get($container, ResponseFactoryInterface::class),
                    viewResponse: ContainerDependency::get($container, ViewResponseFactoryInterface::class),
                    requestFormat: ContainerDependency::get($container, RequestFormat::class),
                    routeNamespaceResolver: ContainerDependency::get($container, ViewRouteNamespaceResolver::class),
                    exceptionReporter: ContainerDependency::get($container, ExceptionReporterInterface::class),
                    fallbackPath: $pathManager->get(PathName::ERRORS_FALLBACK_VIEWS),
                );
            },
            debugHttpHandlerFactory: fn(): PrettyPageHandler => new PrettyPageHandler(),
        ));
    }
}
