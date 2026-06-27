<?php declare(strict_types=1);

namespace Concept\App\Telemetry\Subscribers;

use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryEvent;
use Concept\Extensions\Telemetry\TelemetryKey;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;

final class ExtensionsTelemetrySubscriber implements ListenerSubscriber
{
    public function __construct(private readonly TelemetryCollector $collector) {}

    public function subscribeListeners(ListenerRegistry $listenerRegistry): void
    {
        $listenerRegistry->subscribeTo(ExtensionAwakened::class, $this->onExtensionAwakened(...));
    }

    private function onExtensionAwakened(ExtensionAwakened $event): void
    {
        $this->collector->record(TelemetryEvent::FRAMEWORK_EXTENSION_AWAKENED, [
            TelemetryKey::NAME => $event->extensionName,
            TelemetryKey::ANCHOR => $event->anchorId,
        ]);
    }
}
