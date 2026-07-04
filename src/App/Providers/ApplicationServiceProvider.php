<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\Http\Error\TwigHttpErrorRenderer;
use Concept\App\Middleware\RenderHttpErrorMiddleware;
use Concept\App\View\Twig\TwigAppExtension;
use Concept\Core\Providers\Http\HttpKernelServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\Reporting\WhoopsExceptionReporter;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\View\Support\ViewRouteNamespaceResolver;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const bool DEBUG = true;

    private const string ROUTES_WEB = '/routes/web.php';
    private const string VIEWS_FRONTEND = '/resources/views/frontend';
    private const string VIEWS_ROOT = '/resources/views';
    private const string CACHE_VIEWS = '/storage/cache/views';
    private const string ERRORS_FALLBACK = '/resources/views/errors/fallback';
    private const string LOG_APP_FILE = '/storage/logs/app.log';
    private const string LOG_LEVEL = 'debug';
    private const int LOG_MAX_FILES = 7;
    private const string LOG_CHANNEL = 'app';

    /**
     * @param string $root
     */
    public function __construct(private readonly string $root) {}

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
        $fallbackPath = $this->root . self::ERRORS_FALLBACK;

        $container->addServiceProvider(new LoggerMonologServiceProvider(
            path: $this->root . self::LOG_APP_FILE,
            level: self::LOG_LEVEL,
            maxFiles: self::LOG_MAX_FILES,
            channel: self::LOG_CHANNEL,
        ));

        $container->addServiceProvider(new HttpServiceProvider());

        $container->addServiceProvider(new ViewServiceProvider(
            paths: [
                'frontend' => $this->root . self::VIEWS_FRONTEND,
            ],
            extensions: [
                TwigAppExtension::class,
            ],
        ));

        $container->addServiceProvider(new TwigViewServiceProvider(
            viewsPath: $this->root . self::VIEWS_ROOT,
            cacheDir: $this->root . self::CACHE_VIEWS,
            debug: self::DEBUG,
        ));

        $container->add(ExceptionReporterInterface::class, fn(): WhoopsExceptionReporter => new WhoopsExceptionReporter($container))
            ->setShared(true);

        $container->add(TwigHttpErrorRenderer::class, function() use ($container, $fallbackPath): TwigHttpErrorRenderer {
            return new TwigHttpErrorRenderer(
                responseFactory: $container->get(ResponseFactoryInterface::class),
                viewResponse: $container->get(ViewResponseFactoryInterface::class),
                requestFormat: $container->get(RequestFormat::class),
                routeNamespaceResolver: $container->get(ViewRouteNamespaceResolver::class),
                exceptionReporter: $container->get(ExceptionReporterInterface::class),
                fallbackPath: $fallbackPath,
            );
        });

        $container->add(HttpErrorRendererInterface::class, fn(): TwigHttpErrorRenderer => $container->get(TwigHttpErrorRenderer::class))
            ->setShared(true);

        $container->addServiceProvider(new HttpKernelServiceProvider(
            routePaths: [
                $this->root . self::ROUTES_WEB,
            ],
            notFoundMiddleware: RenderHttpErrorMiddleware::class,
        ));

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            debug: self::DEBUG,
            errorsFallbackPath: $fallbackPath,
            exceptionReporter: fn(): ExceptionReporterInterface => $container->get(ExceptionReporterInterface::class),
            httpErrorRenderer: fn(): HttpErrorRendererInterface => $container->get(HttpErrorRendererInterface::class),
        ));
    }
}
