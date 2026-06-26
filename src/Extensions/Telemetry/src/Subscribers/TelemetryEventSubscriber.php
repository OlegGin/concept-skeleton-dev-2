<?php declare(strict_types=1);

namespace Concept\Extensions\Telemetry\Subscribers;

use Concept\Core\Events\Http\RouteHandlerInvoked;
use Concept\Core\Events\Http\RouteInterceptorInvoked;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryEvent;
use Concept\Extensions\Telemetry\TelemetryKey;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;

final class TelemetryEventSubscriber implements ListenerSubscriber
{
    public function __construct(private readonly TelemetryCollector $collector) {}

    public function subscribeListeners(ListenerRegistry $listenerRegistry): void
    {
        $listenerRegistry->subscribeTo(RouteInterceptorInvoked::class, $this->onRouteInterceptorInvoked(...));
        $listenerRegistry->subscribeTo(RouteHandlerInvoked::class, $this->onRouteHandlerInvoked(...));
    }

    private function onRouteInterceptorInvoked(RouteInterceptorInvoked $event): void
    {
        $this->collector->record(TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED, [
            TelemetryKey::NAME => $event->interceptorClass,
        ]);
    }

    private function onRouteHandlerInvoked(RouteHandlerInvoked $event): void
    {
        $this->collector->record(
            TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED,
            [
                TelemetryKey::ROUTE => $event->route,
                TelemetryKey::HANDLER => $event->handler,
            ],
            $event->duration,
            $event->startedAt,
        );
    }
}
