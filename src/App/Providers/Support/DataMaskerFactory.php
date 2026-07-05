<?php declare(strict_types=1);

namespace Concept\App\Providers\Support;

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
        return fn(): ?DataMaskerInterface => $container->get(DataMaskerInterface::class);
    }
}
