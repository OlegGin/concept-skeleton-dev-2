<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Foundation\PathName;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\ConfigServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\PathManager\PathManagerServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class FoundationBootstrap extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @param string $root
     * @param array<string, string> $pathMap
     */
    public function __construct(
        private readonly string $root,
        private readonly array $pathMap,
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

        $container->addServiceProvider(new PathManagerServiceProvider(
            root: $this->root,
            pathMap: $this->pathMap,
        ));

        $pathManager = ContainerDependency::get($container, PathManager::class);

        $container->addServiceProvider(new ConfigServiceProvider(
            root: $this->root,
            configDirectory: $pathManager->get(PathName::CONFIG),
        ));
    }
}
