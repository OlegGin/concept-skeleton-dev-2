<?php declare(strict_types=1);

namespace Concept\Bricks\Cache;

use Psr\SimpleCache\CacheInterface;

final class CacheBuilder
{
    public function __construct(
        private readonly CacheOptions $options,
    ) {}

    /**
     * Explicit PSR-16 cache instance. Stack does not choose storage — pass a ready adapter from app glue.
     */
    public function setCache(CacheInterface $cache): self
    {
        $this->options->setCache($cache);

        return $this;
    }
}
