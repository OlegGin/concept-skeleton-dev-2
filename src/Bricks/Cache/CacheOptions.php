<?php declare(strict_types=1);

namespace Concept\Bricks\Cache;

use Psr\SimpleCache\CacheInterface;
use RuntimeException;

final class CacheOptions
{
    private ?CacheInterface $cache = null;

    private const string ERR_CACHE = 'Cache capability requires setCache() with a PSR-16 cache instance.';

    public function cache(): ?CacheInterface
    {
        return $this->cache;
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function assertValid(): void
    {
        if ($this->cache === null) {
            throw new RuntimeException(self::ERR_CACHE);
        }
    }
}
