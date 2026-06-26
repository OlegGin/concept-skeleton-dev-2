<?php declare(strict_types=1);

namespace Concept\Components\Telemetry\Subscribers;

use Concept\Extensions\FormRequest\Events\FormRequestValidated;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryEvent;
use Concept\Extensions\Telemetry\TelemetryKey;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;

final class FormRequestTelemetrySubscriber implements ListenerSubscriber
{
    public function __construct(private readonly TelemetryCollector $collector) {}

    public function subscribeListeners(ListenerRegistry $listenerRegistry): void
    {
        $listenerRegistry->subscribeTo(FormRequestValidated::class, $this->onFormRequestValidated(...));
    }

    private function onFormRequestValidated(FormRequestValidated $event): void
    {
        $this->collector->record(
            TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED,
            [TelemetryKey::NAME => $event->formRequestClass],
            $event->duration,
            $event->startedAt,
        );
    }
}
