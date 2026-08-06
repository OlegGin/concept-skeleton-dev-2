<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Foundation\PathName;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\ConfigServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\PathManager\PathManagerServiceProvider;
use League\Container\Container;

final class FoundationBootstrap
{
    /**
     * @param array<string, string> $pathMap
     */
    public function __construct(
        private readonly string $root,
        private readonly array $pathMap,
    ) {}

    public function boot(Container $container): void
    {
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
