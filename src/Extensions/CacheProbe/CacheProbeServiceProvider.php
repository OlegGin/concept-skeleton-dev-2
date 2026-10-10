<?php declare(strict_types=1);

namespace Concept\Extensions\CacheProbe;

use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\SimpleCache\CacheInterface;

final class CacheProbeServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === CacheProbe::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(CacheProbe::class, static function() use ($container): CacheProbe {
            return new CacheProbe(
                cache: ContainerDependency::get($container, CacheInterface::class),
                logger: ContainerDependency::get($container, LoggerInterface::class),
            );
        })->setShared(true);
    }
}
