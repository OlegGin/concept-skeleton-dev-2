<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Core\Services\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Core\Services\Telemetry\TelemetryCollector;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

final class ServicesDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'services';

    public function __construct(
        private readonly TelemetryCollector $telemetryCollector,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{data: array<string, string>, count: int}
     */
    public function collect(): array
    {
        $data = [];
        $servicesAwakening = $this->telemetryCollector->items(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING);
        foreach ($servicesAwakening as $serviceAwakening) {
            /** @var TelemetryItemInterface $serviceAwakening */
            $name = $this->getContextAttribute($serviceAwakening, 'name');
            $key = $this->shortClassName($name);
            $value = sprintf('%s (%s)', $this->formatStartedAt($serviceAwakening->getStartedAt()), $name);
            $data[$key] = $value;
        }

        return [
            'data' => $data,
            'count' => count($servicesAwakening),
        ];
    }

    /**
     * @return array<string, array{icon?: string, widget?: string, map?: string, default?: mixed}>
     */
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                'icon' => 'tags',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => self::NAME . '.data',
                'default' => '{}',
            ],
            self::NAME . ':badge' => [
                'map' => self::NAME . '.count',
                'default' => 0,
            ],
        ];
    }

    private function shortClassName(string $service): string
    {
        $segments = explode('\\', $service);

        return end($segments) ?: $service;
    }
}
