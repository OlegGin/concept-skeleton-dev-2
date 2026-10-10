<?php declare(strict_types=1);

namespace Concept\Bricks\CacheProbe;

use Concept\Extensions\CacheProbe\CacheProbeServiceProvider;
use Concept\Stack\Capability\Capability;
use Concept\Stack\Contract\BrickInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * Demo custom brick that depends on other capabilities.
 *
 * requires:
 *   - cache   (app CacheBrick via addCustom)
 *   - logging (built-in addLogging)
 *
 * Registration order of addCustom/addLogging does not matter for wiring —
 * CapabilityRegistry topologically sorts providers so dependencies come first.
 *
 *   $stack->addCustom(new CacheProbeBrick());
 */
final class CacheProbeBrick implements BrickInterface
{
    private const string NAME = 'cache-probe';

    public function name(): string
    {
        return self::NAME;
    }

    public function requires(): array
    {
        return [
            'cache',
            Capability::LOGGING,
        ];
    }

    /**
     * @return list<ServiceProviderInterface>
     */
    public function providers(): array
    {
        return [new CacheProbeServiceProvider()];
    }

    public function assertValid(): void
    {
    }
}
