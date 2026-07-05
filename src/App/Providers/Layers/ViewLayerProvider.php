<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Providers\Support\ApplicationPaths;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ViewLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
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
        $paths = new ApplicationPaths($pathManager);

        /** @var array<string, string> $viewPaths */
        $viewPaths = $config->get(ConfigKey::VIEW_PATHS) ?? [];
        /** @var array<string, string> $routeNamespace */
        $routeNamespace = $config->get(ConfigKey::VIEW_ROUTE_NAMESPACE) ?? [];
        /** @var list<class-string> $viewExtensions */
        $viewExtensions = $config->get(ConfigKey::VIEW_EXTENSIONS) ?? [];
        $container->addServiceProvider(new ViewServiceProvider(
            paths: $paths->resolveMap($viewPaths),
            extensions: $viewExtensions,
            routeNamespace: $routeNamespace,
        ));

        $container->addServiceProvider(new TwigViewServiceProvider(
            viewsPath: $pathManager->get(PathName::VIEWS),
            cacheDir: $pathManager->get(PathName::CACHE, $config->getString(ConfigKey::VIEW_CACHE_DIR)),
            debug: $config->getBool(ConfigKey::APP_DEBUG),
        ));
    }
}
