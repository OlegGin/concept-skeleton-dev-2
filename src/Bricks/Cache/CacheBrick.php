<?php declare(strict_types=1);

namespace Concept\Bricks\Cache;

use Concept\Extensions\Cache\CacheServiceProvider;
use Concept\Stack\Contract\BrickInterface;
use Concept\Stack\Exceptions\InvalidCapabilityOptionsException;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * App-level cache brick (skeleton). Not part of php-concept/stack.
 *
 *   $stack->addCustom(
 *       (new CacheBrick())->setCache(new Psr16Cache(new FilesystemAdapter(...)))
 *   );
 */
final class CacheBrick implements BrickInterface
{
    private const string NAME = 'cache';

    private ?CacheInterface $cache = null;

    public function name(): string
    {
        return self::NAME;
    }

    public function requires(): array
    {
        return [];
    }

    /**
     * Explicit PSR-16 instance. Stack does not choose storage — pass a ready adapter from glue.
     */
    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return list<ServiceProviderInterface>
     */
    public function providers(): array
    {
        $this->assertValid();

        /** @var CacheInterface $cache */
        $cache = $this->cache;

        return [new CacheServiceProvider($cache)];
    }

    public function assertValid(): void
    {
        if ($this->cache === null) {
            throw InvalidCapabilityOptionsException::missingOption(self::NAME, 'setCache()');
        }
    }
}
