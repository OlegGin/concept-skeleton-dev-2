<?php declare(strict_types=1);

namespace Concept\Bricks\Cache;

use Concept\Extensions\Cache\CacheServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use RuntimeException;

final class CacheStackProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string ERR_CACHE = 'Cache capability requires setCache() with a PSR-16 cache instance.';

    public function __construct(
        private readonly CacheOptions $options,
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
        $cache = $this->options->cache();
        if ($cache === null) {
            throw new RuntimeException(self::ERR_CACHE);
        }

        $this->getContainer()->addServiceProvider(new CacheServiceProvider(cache: $cache));
    }
}
