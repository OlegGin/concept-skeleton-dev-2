<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\Middleware\RenderHttpErrorMiddleware;
use Concept\App\View\Twig\TwigAppExtension;
use Concept\Core\Providers\Http\HttpKernelServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\Http\HttpServiceProvider;
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

        $container->addServiceProvider(new HttpKernelServiceProvider(
            routePaths: [
                $this->root . self::ROUTES_WEB,
            ],
            notFoundMiddleware: RenderHttpErrorMiddleware::class,
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

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            debug: self::DEBUG,
            errorsFallbackPath: $this->root . self::ERRORS_FALLBACK,
        ));
    }
}
