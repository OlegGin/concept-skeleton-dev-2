<?php declare(strict_types=1);

namespace Concept\Components\Telemetry\Subscribers;

use Concept\Extensions\Components\Events\ComponentRegistered;
use Concept\Extensions\Components\Events\ComponentRoutesRegistered;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryEvent;
use Concept\Extensions\Telemetry\TelemetryKey;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;

final class ComponentsTelemetrySubscriber implements ListenerSubscriber
{
    public function __construct(private readonly TelemetryCollector $collector) {}

    public function subscribeListeners(ListenerRegistry $listenerRegistry): void
    {
        $listenerRegistry->subscribeTo(ComponentRegistered::class, $this->onComponentRegistered(...));
        $listenerRegistry->subscribeTo(ComponentRoutesRegistered::class, $this->onComponentRoutesRegistered(...));
    }

    private function onComponentRegistered(ComponentRegistered $event): void
    {
        $this->collector->record(TelemetryEvent::FRAMEWORK_COMPONENT_REGISTERED, [
            TelemetryKey::NAME => $event->componentName,
            TelemetryKey::HANDLER => $event->componentClass,
        ]);
    }

    private function onComponentRoutesRegistered(ComponentRoutesRegistered $event): void
    {
        $this->collector->record(TelemetryEvent::FRAMEWORK_ROUTES_REGISTERED, [
            TelemetryKey::COUNT => $event->routesFileCount,
        ]);
    }
}
