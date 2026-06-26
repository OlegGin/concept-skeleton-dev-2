<?php declare(strict_types=1);

namespace Concept\Extensions\Telemetry\Subscribers;

use Concept\Extensions\Telemetry\TelemetryCollector;
use League\Event\ListenerSubscriber;
use ReflectionClass;

final class TelemetrySubscriberFactory
{
    /**
     * @param class-string<ListenerSubscriber> $subscriberClass
     */
    public static function create(string $subscriberClass, TelemetryCollector $collector): ListenerSubscriber
    {
        /** @var ListenerSubscriber $subscriber */
        $subscriber = new ReflectionClass($subscriberClass)->newInstance($collector);

        return $subscriber;
    }
}
