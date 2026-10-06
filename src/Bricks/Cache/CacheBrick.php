<?php declare(strict_types=1);

namespace Concept\Bricks\Cache;

use Concept\Stack\Builder\StackBuilder;

/**
 * App-level cache capability (skeleton). Not part of php-concept/stack.
 *
 * Usage:
 *   $cache = new Psr16Cache(new FilesystemAdapter(...));
 *   CacheBrick::add($stack)->setCache($cache);
 */
final class CacheBrick
{
    public static function add(StackBuilder $stack): CacheBuilder
    {
        $options = new CacheOptions();
        $stack->registerCapability(
            CacheCapability::NAME,
            new CacheStackProvider($options),
            assertValid: static fn() => $options->assertValid(),
        );

        return new CacheBuilder($options);
    }
}
