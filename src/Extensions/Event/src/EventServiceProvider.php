<?php declare(strict_types=1);

namespace Concept\Extensions\Event;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Event\EventDispatcher;
use League\Event\ListenerSubscriber;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventServiceProvider extends AbstractServiceProvider
{
    /**
     * @param list<class-string<ListenerSubscriber>> $subscriberClasses
     */
    public function __construct(private readonly array $subscriberClasses = []) {}

    public function provides(string $id): bool
    {
        return $id === EventDispatcherInterface::class || $id === EventDispatcher::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $subscriberClasses = $this->subscriberClasses;

        $container->add(EventDispatcherInterface::class, function () use ($container, $subscriberClasses): EventDispatcher {
            $dispatcher = new EventDispatcher();
            foreach ($subscriberClasses as $subscriberClass) {
                /** @var ListenerSubscriber $subscriber */
                $subscriber = $container->get($subscriberClass);
                $dispatcher->subscribeListenersFrom($subscriber);
            }

            return $dispatcher;
        })->setShared(true);

        $container->add(EventDispatcher::class, function () use ($container): EventDispatcher {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $container->get(EventDispatcherInterface::class);

            return $dispatcher;
        })->setShared(true);
    }
}
