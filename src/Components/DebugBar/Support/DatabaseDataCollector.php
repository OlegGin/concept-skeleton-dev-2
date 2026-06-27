<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\App\Telemetry\TelemetryEvent;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class DatabaseDataCollector extends DataCollector implements Renderable
{
    private const NAME = 'database';

    public function __construct(
        private readonly TelemetryCollector $telemetryCollector,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function collect(): array
    {
        $statements = [];
        $totalTime = 0;
        $queries = $this->telemetryCollector->toArray(TelemetryEvent::DB_QUERY_EXECUTED);
        foreach ($queries as $query) {
            /** @var array{context: array{sql: string, raw: string, time: float, bindings: array<mixed>, connection: string}} $query */
            $statements[] = [
                'sql' => $query['context']['sql'],
                'backtrace' => [$query['context']['raw']],
                'duration' => $query['context']['time'] / 1000,
                'duration_str' => $this->formatDuration($query['context']['time']),
                'params' => $query['context']['bindings'],
                'connection' => $query['context']['connection']
            ];
            $totalTime += $query['context']['time'];
        }

        return [
            'nb_statements' => count($queries),
            'nb_failed_statements' => 0,
            'accumulated_duration' => $totalTime / 1000,
            'accumulated_duration_str' => $this->formatDuration($totalTime),
            'statements' => $statements,
        ];
    }

    /**
     * @return array<string, array{icon?: string, widget?: string, map?: string, default?: mixed}>
     */
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                'icon' => 'database',
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => self::NAME,
                'default' => '[]'
            ],
            self::NAME . ':badge' => [
                'map' => self::NAME . '.nb_statements',
                'default' => 0
            ]
        ];
    }

    /**
     * @param float|int $milliseconds
     * @return string
     */
    public function formatDuration($milliseconds): string
    {
        return sprintf('%sms', $milliseconds);
    }
}