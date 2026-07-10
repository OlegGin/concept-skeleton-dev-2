<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Closure;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Http\Error\AppExceptionReporter;
use Concept\App\Http\Error\Contracts\ExceptionReporterInterface;
use Concept\App\Http\Error\Contracts\HttpErrorRendererInterface;
use Concept\App\Http\Error\Handlers\RenderHttpErrorHandler;
use Concept\App\Http\Error\Handlers\ReportExceptionHandler;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\PlainTextHandler;

final class ErrorHandlingLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @param Closure(DefinitionContainerInterface): HttpErrorRendererInterface $httpErrorRendererFactory
     * @param Closure(): Handler|null $debugHttpHandlerFactory Used in web debug instead of HttpErrorRenderer (e.g. PrettyPage)
     */
    public function __construct(
        private readonly Closure $httpErrorRendererFactory,
        private readonly ?Closure $debugHttpHandlerFactory = null,
    ) {}

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
        $config = ContainerDependency::get($container, ConfigInterface::class);

        $container->add(ExceptionReporterInterface::class, function() use ($container): AppExceptionReporter {
            return new AppExceptionReporter(
                logger: ContainerDependency::get($container, LoggerInterface::class),
                container: $container,
            );
        })->setShared(true);

        $container->add(HttpErrorRendererInterface::class, function() use ($container): HttpErrorRendererInterface {
            return ($this->httpErrorRendererFactory)($container);
        })->setShared(true);

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            handlers: $this->buildAwakeHandlers($container, $config->getBool(ConfigKey::APP_DEBUG)),
        ));
    }

    /**
     * @return list<Handler>
     */
    private function buildAwakeHandlers(DefinitionContainerInterface $container, bool $debug): array
    {
        $handlers = [
            new ReportExceptionHandler(
                fn(): ExceptionReporterInterface => ContainerDependency::get($container, ExceptionReporterInterface::class),
            ),
        ];

        if (PHP_SAPI === 'cli') {
            $handlers[] = new PlainTextHandler();

            return $handlers;
        }

        if ($debug && $this->debugHttpHandlerFactory !== null && !$this->requestExpectsJson($container)) {
            $handlers[] = ($this->debugHttpHandlerFactory)();

            return $handlers;
        }

        $handlers[] = new RenderHttpErrorHandler(
            fn(): HttpErrorRendererInterface => ContainerDependency::get($container, HttpErrorRendererInterface::class),
            $container,
        );

        return $handlers;
    }

    private function requestExpectsJson(DefinitionContainerInterface $container): bool
    {
        if (!$container->has(ServerRequestInterface::class) || !$container->has(RequestFormat::class)) {
            return false;
        }

        $request = ContainerDependency::get($container, ServerRequestInterface::class);
        $requestFormat = ContainerDependency::get($container, RequestFormat::class);

        return $requestFormat->expectsJson($request);
    }
}
