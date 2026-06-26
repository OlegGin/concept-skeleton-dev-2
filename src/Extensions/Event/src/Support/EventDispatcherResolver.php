<?php declare(strict_types=1);

namespace Concept\Extensions\Event\Support;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatcherResolver
{
    public static function optional(ContainerInterface $container): ?EventDispatcherInterface
    {
        if (!$container->has(EventDispatcherInterface::class)) {
            return null;
        }

        $dispatcher = $container->get(EventDispatcherInterface::class);

        return $dispatcher instanceof EventDispatcherInterface ? $dispatcher : null;
    }
}
