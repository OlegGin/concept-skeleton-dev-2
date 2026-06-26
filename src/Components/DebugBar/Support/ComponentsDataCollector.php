<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Extensions\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryEvent;
use Concept\Extensions\Telemetry\TelemetryKey;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

final class ComponentsDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'components';

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
        $registered = $this->telemetryCollector->items(TelemetryEvent::FRAMEWORK_COMPONENT_REGISTERED);
        foreach ($registered as $item) {
            /** @var TelemetryItemInterface $item */
            $componentName = $this->stringValue($item->getContext(), TelemetryKey::NAME);
            $componentClass = $this->stringValue($item->getContext(), TelemetryKey::HANDLER);
            $data[$componentName] = $componentClass;
        }

        return [
            'data' => $data,
            'count' => count($registered),
        ];
    }

    /**
     * @return array<string, array{icon?: string, widget?: string, map?: string, default?: mixed}>
     */
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                'icon' => 'puzzle-piece',
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

    /**
     * @param array<mixed> $context
     */
    private function stringValue(array $context, string $key): string
    {
        $value = $context[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
