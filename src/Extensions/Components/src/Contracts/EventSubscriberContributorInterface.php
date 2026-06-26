<?php declare(strict_types=1);

namespace Concept\Extensions\Components\Contracts;

use League\Event\ListenerSubscriber;

interface EventSubscriberContributorInterface
{
    /**
     * @return list<class-string<ListenerSubscriber>>
     */
    public function eventSubscribers(): array;
}
