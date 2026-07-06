<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Http\Error\AppExceptionReporter;
use Concept\App\Http\Error\TwigHttpErrorRenderer;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\View\Support\ViewRouteNamespaceResolver;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ErrorHandlingLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
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
        $container = $this->getContainer();

        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);

        $fallbackPath = $pathManager->get(PathName::ERRORS_FALLBACK_VIEWS);

        $container->add(ExceptionReporterInterface::class, function() use ($container): AppExceptionReporter {
            return new AppExceptionReporter(
                logger: ContainerDependency::get($container, LoggerInterface::class),
                container: $container,
            );
        })->setShared(true);

        $container->add(TwigHttpErrorRenderer::class, function() use ($container, $fallbackPath): TwigHttpErrorRenderer {
            return new TwigHttpErrorRenderer(
                responseFactory: ContainerDependency::get($container, ResponseFactoryInterface::class),
                viewResponse: ContainerDependency::get($container, ViewResponseFactoryInterface::class),
                requestFormat: ContainerDependency::get($container, RequestFormat::class),
                routeNamespaceResolver: ContainerDependency::get($container, ViewRouteNamespaceResolver::class),
                exceptionReporter: ContainerDependency::get($container, ExceptionReporterInterface::class),
                fallbackPath: $fallbackPath,
            );
        });

        $container->add(
            HttpErrorRendererInterface::class,
            fn(): TwigHttpErrorRenderer => ContainerDependency::get($container, TwigHttpErrorRenderer::class),
        )->setShared(true);

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            errorsFallbackPath: $fallbackPath,
            exceptionReporter: fn(): ExceptionReporterInterface => ContainerDependency::get($container, ExceptionReporterInterface::class),
            httpErrorRenderer: fn(): HttpErrorRendererInterface => ContainerDependency::get($container, HttpErrorRendererInterface::class),
        ));
    }
}
