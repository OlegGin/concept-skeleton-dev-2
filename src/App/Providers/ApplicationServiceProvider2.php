<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\Core\Providers\Http\HttpKernelServiceProvider;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const bool DEBUG = true;

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
                $this->root . '/routes/web.php',
            ],
            resolvers: [
            ],
        ));

        $container->addServiceProvider(new HttpServiceProvider());

        $container->addServiceProvider(new ViewServiceProvider(
            paths: [
                'frontend' => $this->root . '/resources/views/frontend',
            ],
        ));

        $container->addServiceProvider(new TwigViewServiceProvider(
            viewsPath: $this->root . '/resources/views',
            cacheDir: $this->root . '/storage/cache/views',
            debug: self::DEBUG,
        ));
    }
}
