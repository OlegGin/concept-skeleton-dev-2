<?php declare(strict_types=1);

namespace Concept\App\Providers\Support;

use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Closure;
use Psr\Container\ContainerInterface;

final class DataMaskerFactory
{
    /**
     * @return Closure(): ?DataMaskerInterface
     */
    public static function fromContainer(ContainerInterface $container): Closure
    {
        return function() use ($container): ?DataMaskerInterface {
            if (!$container->has(DataMaskerInterface::class)) {
                return null;
            }

            return ContainerDependency::get($container, DataMaskerInterface::class);
        };
    }
}
