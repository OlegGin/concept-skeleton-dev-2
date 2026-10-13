<?php declare(strict_types=1);

namespace Concept\Bricks\CacheProbe;

use Concept\Bricks\Cache\CacheBrick;
use Concept\Extensions\CacheProbe\CacheProbeServiceProvider;
use Concept\Stack\Bricks\Logging\LoggingBrick;
use Concept\Stack\Contract\BrickInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * Demo custom brick that depends on other capabilities.
 *
 * requires:
 *   - cache   (app CacheBrick)
 *   - logging (LoggingBrick::NAME)
 *
 *   $stack->add(new CacheProbeBrick());
 */
final class CacheProbeBrick implements BrickInterface
{
    public const string NAME = 'cache-probe';

    public function name(): string
    {
        return self::NAME;
    }

    public function requires(): array
    {
        return [
            CacheBrick::NAME,
            LoggingBrick::NAME,
        ];
    }

    /**
     * @return list<ServiceProviderInterface>
     */
    public function providers(): array
    {
        return [new CacheProbeServiceProvider()];
    }
}
