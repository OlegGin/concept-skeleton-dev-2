<?php declare(strict_types=1);

namespace Concept\Extensions\Telemetry\Subscribers;

use Concept\Core\Events\Http\RequestHandled;
use Concept\Core\Events\Http\RouteHandlerInvoked;
use Concept\Core\Events\Http\RouteInterceptorInvoked;
use Concept\Extensions\Components\Events\ComponentRegistered;
use Concept\Extensions\Components\Events\ComponentRoutesRegistered;
use Concept\Extensions\DatabaseEloquent\Events\DatabaseQueryExecuted;
use Concept\Extensions\FormRequest\Events\FormRequestValidated;
use Concept\Extensions\View\Events\TemplateRendered;
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
        $listenerRegistry->subscribeTo(FormRequestValidated::class, $this->onFormRequestValidated(...));
        $listenerRegistry->subscribeTo(RequestHandled::class, $this->onRequestHandled(...));
        $listenerRegistry->subscribeTo(DatabaseQueryExecuted::class, $this->onDatabaseQueryExecuted(...));
        $listenerRegistry->subscribeTo(TemplateRendered::class, $this->onTemplateRendered(...));
        $listenerRegistry->subscribeTo(ComponentRegistered::class, $this->onComponentRegistered(...));
        $listenerRegistry->subscribeTo(ComponentRoutesRegistered::class, $this->onComponentRoutesRegistered(...));
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

    private function onFormRequestValidated(FormRequestValidated $event): void
    {
        $this->collector->record(
            TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED,
            [TelemetryKey::NAME => $event->formRequestClass],
            $event->duration,
            $event->startedAt,
        );
    }

    private function onRequestHandled(RequestHandled $event): void
    {
        $this->collector->record(
            TelemetryEvent::HTTP_REQUEST_HANDLED,
            [
                TelemetryKey::METHOD => $event->method,
                TelemetryKey::PATH => $event->path,
                TelemetryKey::MEMORY_START => $event->memoryStart,
                TelemetryKey::MEMORY_END => $event->memoryEnd,
                TelemetryKey::MEMORY_PEAK => $event->memoryPeak,
            ],
            $event->duration,
            $event->startedAt,
        );
    }

    private function onDatabaseQueryExecuted(DatabaseQueryExecuted $event): void
    {
        $this->collector->record(TelemetryEvent::DB_QUERY_EXECUTED, [
            TelemetryKey::SQL => $event->sql,
            TelemetryKey::RAW => $event->rawSql,
            TelemetryKey::BINDINGS => $event->bindings,
            TelemetryKey::TIME => $event->time,
            TelemetryKey::CONNECTION => $event->connectionName,
        ]);
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
