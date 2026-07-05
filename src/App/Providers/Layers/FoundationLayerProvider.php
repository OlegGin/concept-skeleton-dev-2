<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\PathName;
use Concept\Extensions\Config\ConfigServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\PathManager\PathManagerServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class FoundationLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
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

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);

        $container->addServiceProvider(new ConfigServiceProvider(
            root: $this->root,
            configDirectory: $pathManager->get(PathName::CONFIG),
        ));
    }
}
