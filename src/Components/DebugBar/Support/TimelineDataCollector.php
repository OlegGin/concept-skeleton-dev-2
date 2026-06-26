<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Extensions\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryEvent;
use Concept\Extensions\Telemetry\TelemetryKey;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use League\Route\Route;

/**
 * @phpstan-type TimelineMeasure array{
 *     label: string,
 *     start: float,
 *     relative_start: float,
 *     end: float,
 *     relative_end: float,
 *     duration: float,
 *     duration_str: string,
 *     memory: int,
 *     memory_str: string,
 *     params: array<string, mixed>,
 *     collector: string,
 *     group: string,
 * }
 */
final class TimelineDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'timeline';

    /** @var list<string> */
    private const array EVENTS = [
        TelemetryEvent::FRAMEWORK_COMPONENT_REGISTERED,
        TelemetryEvent::FRAMEWORK_ROUTES_REGISTERED,
        TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED,
        TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED,
        TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED,
        TelemetryEvent::HTTP_REQUEST_HANDLED,
        TelemetryEvent::TPL_RENDERED,
    ];

    public function __construct(
        private readonly TelemetryCollector $telemetryCollector,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{
     *     count: int,
     *     start: float,
     *     end: float,
     *     duration: float,
     *     duration_str: string,
     *     measures: list<TimelineMeasure>
     * }
     */
    public function collect(): array
    {
        /** @var list<TimelineMeasure> $measures */
        $measures = [];

        foreach (self::EVENTS as $event) {
            foreach ($this->telemetryCollector->items($event) as $item) {
                /** @var TelemetryItemInterface $item */
                $measure = $this->buildMeasure($event, $item);
                if ($measure !== null) {
                    $measures[] = $measure;
                }
            }
        }

        if ($measures === []) {
            $now = microtime(true);

            return [
                'count' => 0,
                'start' => $now,
                'end' => $now,
                'duration' => 0.0,
                'duration_str' => $this->formatDuration(0.0),
                'measures' => [],
            ];
        }

        usort($measures, static fn (array $left, array $right): int => $left['start'] <=> $right['start']);

        $timelineStart = $measures[0]['start'];
        $timelineEnd = $timelineStart;
        foreach ($measures as $measure) {
            $timelineEnd = max($timelineEnd, $measure['end']);
        }

        $duration = $timelineEnd - $timelineStart;

        foreach ($measures as &$measure) {
            $measure['relative_start'] = $measure['start'] - $timelineStart;
            $measure['relative_end'] = $measure['end'] - $timelineEnd;
        }
        unset($measure);

        return [
            'count' => count($measures),
            'start' => $timelineStart,
            'end' => $timelineEnd,
            'duration' => $duration,
            'duration_str' => $this->formatDuration($duration),
            'measures' => $measures,
        ];
    }

    /**
     * @return array<string, array{icon?: string, widget?: string, map?: string, default?: mixed}>
     */
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                'icon' => 'tasks',
                'widget' => 'PhpDebugBar.Widgets.TimelineWidget',
                'map' => self::NAME,
                'default' => '[]',
            ],
            self::NAME . ':badge' => [
                'map' => self::NAME . '.count',
                'default' => 0,
            ],
        ];
    }

    /**
     * @return TimelineMeasure|null
     */
    private function buildMeasure(string $event, TelemetryItemInterface $item): ?array
    {
        $startedAt = $item->getStartedAt();
        $finishedAt = $item->getFinishedAt();
        if ($startedAt === null || $finishedAt === null) {
            return null;
        }

        $duration = $item->getDuration() ?? ($finishedAt - $startedAt);
        $start = $startedAt;
        if ($duration > 0.0 && ($finishedAt - $start) < $duration) {
            $start = $finishedAt - $duration;
        }

        $context = $item->getContext();
        $memory = $this->memoryUsage($event, $context);

        return [
            'label' => $this->buildLabel($event, $context),
            'start' => $start,
            'relative_start' => 0.0,
            'end' => $finishedAt,
            'relative_end' => 0.0,
            'duration' => $duration,
            'duration_str' => $this->formatDuration($duration),
            'memory' => $memory,
            'memory_str' => $this->getDataFormatter()->formatBytes($memory),
            'params' => $this->buildParams($context),
            'collector' => $this->collectorName($event),
            'group' => $this->groupName($event),
        ];
    }

    /**
     * @param array<mixed> $context
     */
    private function buildLabel(string $event, array $context): string
    {
        return match ($event) {
            TelemetryEvent::FRAMEWORK_COMPONENT_REGISTERED => $this->stringValue($context, TelemetryKey::NAME),
            TelemetryEvent::FRAMEWORK_ROUTES_REGISTERED => sprintf(
                'routes (%d files)',
                $this->intValue($context, TelemetryKey::COUNT),
            ),
            TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED => $this->stringValue($context, TelemetryKey::NAME),
            TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED => $this->stringValue($context, TelemetryKey::HANDLER),
            TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED => $this->stringValue($context, TelemetryKey::NAME),
            TelemetryEvent::HTTP_REQUEST_HANDLED => trim(sprintf(
                '%s %s',
                $this->stringValue($context, TelemetryKey::METHOD),
                $this->stringValue($context, TelemetryKey::PATH),
            )),
            TelemetryEvent::TPL_RENDERED => $this->stringValue($context, TelemetryKey::VIEW),
            default => $event,
        };
    }

    /**
     * @param array<mixed> $context
     * @return array<string, mixed>
     */
    private function buildParams(array $context): array
    {
        $params = [];

        foreach ($context as $key => $value) {
            if (!is_string($key) || $value instanceof Route) {
                continue;
            }

            $params[$key] = $this->getDataFormatter()->formatVar($value);
        }

        return $params;
    }

    /**
     * @param array<mixed> $context
     */
    private function memoryUsage(string $event, array $context): int
    {
        if ($event !== TelemetryEvent::HTTP_REQUEST_HANDLED) {
            return 0;
        }

        $memoryStart = $this->intValue($context, TelemetryKey::MEMORY_START);
        $memoryEnd = $this->intValue($context, TelemetryKey::MEMORY_END);

        return max(0, $memoryEnd - $memoryStart);
    }

    /**
     * @param array<mixed> $context
     */
    private function stringValue(array $context, string $key): string
    {
        $value = $context[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param array<mixed> $context
     */
    private function intValue(array $context, string $key): int
    {
        $value = $context[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    private function collectorName(string $event): string
    {
        return match ($event) {
            TelemetryEvent::FRAMEWORK_COMPONENT_REGISTERED,
            TelemetryEvent::FRAMEWORK_ROUTES_REGISTERED => 'framework',
            TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED,
            TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED,
            TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED,
            TelemetryEvent::HTTP_REQUEST_HANDLED => 'http',
            TelemetryEvent::TPL_RENDERED => 'tpl',
            default => 'other',
        };
    }

    private function groupName(string $event): string
    {
        return match ($event) {
            TelemetryEvent::FRAMEWORK_COMPONENT_REGISTERED => 'component registered',
            TelemetryEvent::FRAMEWORK_ROUTES_REGISTERED => 'routes registered',
            TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED => 'route interceptor',
            TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED => 'route callable',
            TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED => 'form request',
            TelemetryEvent::HTTP_REQUEST_HANDLED => 'request handled',
            TelemetryEvent::TPL_RENDERED => 'template rendered',
            default => $event,
        };
    }
}
