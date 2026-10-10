<?php declare(strict_types=1);

namespace Concept\Extensions\Cache;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\SimpleCache\CacheInterface;

/**
 * App-local cache extension. Registers a ready PSR-16 instance from glue.
 */
final class CacheServiceProvider extends AbstractServiceProvider
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function provides(string $id): bool
    {
        return $id === CacheInterface::class;
    }

    public function register(): void
    {
        $this->getContainer()->addShared(CacheInterface::class, $this->cache);
    }
}
