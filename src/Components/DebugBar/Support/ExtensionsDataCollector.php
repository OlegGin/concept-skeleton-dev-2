<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Extensions\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\App\Telemetry\TelemetryKey;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

final class ExtensionsDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'extensions';

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
        $awakened = $this->telemetryCollector->items(TelemetryEvent::FRAMEWORK_EXTENSION_AWAKENED);
        foreach ($awakened as $item) {
            /** @var TelemetryItemInterface $item */
            $extensionName = $this->stringValue($item->getContext(), TelemetryKey::NAME);
            $anchorId = $this->stringValue($item->getContext(), TelemetryKey::ANCHOR);
            $data[$extensionName] = $anchorId;
        }

        return [
            'data' => $data,
            'count' => count($awakened),
        ];
    }

    /**
     * @return array<string, array{icon?: string, widget?: string, map?: string, default?: mixed}>
     */
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                'icon' => 'cogs',
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
