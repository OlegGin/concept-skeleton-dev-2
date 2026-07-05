<?php declare(strict_types=1);

namespace Concept\App\Providers\Profiles\Minimal;

use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpKernelServiceProvider;
use Concept\Extensions\Http\HttpServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class MinimalHttpServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string ROUTES_API = '/routes/api.php';

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

        $container->addServiceProvider(new HttpServiceProvider());

        $container->addServiceProvider(new HttpKernelServiceProvider(
            routePaths: [
                $this->root . self::ROUTES_API,
            ],
            resolvers: [
                new ServerRequestArgumentResolver(),
                new RouteParameterArgumentResolver(),
            ],
        ));
    }
}
