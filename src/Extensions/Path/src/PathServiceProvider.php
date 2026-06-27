<?php declare(strict_types=1);

namespace Concept\Extensions\Path;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class PathServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @param array<string, string> $pathMap
     */
    public function __construct(
        private readonly string $root,
        private readonly array $pathMap = [],
    ) {}

    public function provides(string $id): bool
    {
        return $id === PathManager::class;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->getContainer()
            ->add(PathManager::class, new PathManager($this->root, $this->pathMap))
            ->setShared(true);
    }
}
