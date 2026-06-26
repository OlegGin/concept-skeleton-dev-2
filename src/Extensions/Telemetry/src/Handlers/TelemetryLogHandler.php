<?php declare(strict_types=1);

namespace Concept\Extensions\Telemetry\Handlers;

use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryEvent;
use Concept\Extensions\Telemetry\TelemetryKey;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\LogRecord;

final class TelemetryLogHandler extends AbstractHandler
{
    public function __construct(
        private readonly TelemetryCollector $collector,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $this->collector->record(
            TelemetryEvent::LOG_RECORDED,
            [
                TelemetryKey::LEVEL => strtolower($record->level->getName()),
                TelemetryKey::MESSAGE => $record->message,
                TelemetryKey::CONTEXT => $record->context,
            ],
        );

        return false === $this->bubble;
    }
}
