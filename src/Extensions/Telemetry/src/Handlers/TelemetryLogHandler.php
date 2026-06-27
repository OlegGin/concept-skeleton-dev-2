<?php declare(strict_types=1);

namespace Concept\Extensions\Telemetry\Handlers;

use Concept\Extensions\Telemetry\TelemetryCollector;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\LogRecord;

final class TelemetryLogHandler extends AbstractHandler
{
    private const string CONTEXT_LEVEL = 'level';
    private const string CONTEXT_MESSAGE = 'message';
    private const string CONTEXT_CONTEXT = 'context';

    public function __construct(
        private readonly TelemetryCollector $collector,
        private readonly string $eventName,
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
            $this->eventName,
            [
                self::CONTEXT_LEVEL => strtolower($record->level->getName()),
                self::CONTEXT_MESSAGE => $record->message,
                self::CONTEXT_CONTEXT => $record->context,
            ],
        );

        return false === $this->bubble;
    }
}
