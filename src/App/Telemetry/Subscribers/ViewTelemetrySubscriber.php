<?php declare(strict_types=1);

namespace Concept\App\Telemetry\Subscribers;

use Concept\Extensions\View\Events\TemplateRendered;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\App\Telemetry\TelemetryKey;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;

final class ViewTelemetrySubscriber implements ListenerSubscriber
{
    public function __construct(private readonly TelemetryCollector $collector) {}

    public function subscribeListeners(ListenerRegistry $listenerRegistry): void
    {
        $listenerRegistry->subscribeTo(TemplateRendered::class, $this->onTemplateRendered(...));
    }

    private function onTemplateRendered(TemplateRendered $event): void
    {
        $this->collector->record(
            TelemetryEvent::TPL_RENDERED,
            [TelemetryKey::VIEW => $event->view],
            $event->duration,
            $event->startedAt,
        );
    }
}
