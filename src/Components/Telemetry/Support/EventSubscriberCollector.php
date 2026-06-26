<?php declare(strict_types=1);

namespace Concept\Components\Telemetry\Support;

use Concept\App\Foundation\ConfigKey;
use Concept\Components\Telemetry\TelemetryComponent;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Telemetry\Subscribers\TelemetryEventSubscriber;
use League\Event\ListenerSubscriber;

final class EventSubscriberCollector
{
    /**
     * @return list<class-string<ListenerSubscriber>>
     */
    public static function collect(ConfigInterface $config): array
    {
        /** @var list<class-string<ListenerSubscriber>> $subscriberClasses */
        $subscriberClasses = $config->get(ConfigKey::EVENTS_SUBSCRIBERS) ?? [];

        $component = new TelemetryComponent();

        return array_values(array_unique([
            TelemetryEventSubscriber::class,
            ...$subscriberClasses,
            ...$component->eventSubscribers(),
        ]));
    }
}
