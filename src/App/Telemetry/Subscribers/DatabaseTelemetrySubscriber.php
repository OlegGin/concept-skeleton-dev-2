<?php declare(strict_types=1);

namespace Concept\App\Telemetry\Subscribers;

use Concept\Extensions\DatabaseEloquent\Events\DatabaseQueryExecuted;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\App\Telemetry\TelemetryKey;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;

final class DatabaseTelemetrySubscriber implements ListenerSubscriber
{
    public function __construct(private readonly TelemetryCollector $collector) {}

    public function subscribeListeners(ListenerRegistry $listenerRegistry): void
    {
        $listenerRegistry->subscribeTo(DatabaseQueryExecuted::class, $this->onDatabaseQueryExecuted(...));
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
}
