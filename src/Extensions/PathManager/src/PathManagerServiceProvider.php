<?php declare(strict_types=1);

namespace Concept\Extensions\PathManager;

use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class PathManagerServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'path-manager';

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
        $this->getContainer()->add(PathManager::class, function(): PathManager {
            $container = $this->getContainer();

            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: PathManager::class,
            ));

            return new PathManager($this->root, $this->pathMap);
        })->setShared(true);
    }
}
