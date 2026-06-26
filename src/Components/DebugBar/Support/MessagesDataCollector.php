<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Core\Services\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Core\Services\Telemetry\TelemetryCollector;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use Concept\Core\Services\Telemetry\TelemetryKey;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

final class MessagesDataCollector extends DataCollector implements Renderable
{
    private const string NAME = 'messages';

    public function __construct(
        private readonly TelemetryCollector $telemetryCollector,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{count: int, messages: list<array<string, mixed>>}
     */
    public function collect(): array
    {
        $messages = [];

        foreach ($this->telemetryCollector->items(TelemetryEvent::LOG_RECORDED) as $item) {
            /** @var TelemetryItemInterface $item */
            $messages[] = $this->formatItem($item);
        }

        usort($messages, static fn (array $left, array $right): int => $left['time'] <=> $right['time']);

        return [
            'count' => count($messages),
            'messages' => $messages,
        ];
    }

    /**
     * @return array<string, array{icon?: string, widget?: string, map?: string, default?: mixed}>
     */
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                'icon' => 'logs',
                'widget' => 'PhpDebugBar.Widgets.MessagesWidget',
                'map' => self::NAME . '.messages',
                'default' => '[]',
            ],
            self::NAME . ':badge' => [
                'map' => self::NAME . '.count',
                'default' => 'null',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatItem(TelemetryItemInterface $item): array
    {
        $context = $item->getContext();
        $message = $this->stringValue($context, TelemetryKey::MESSAGE);
        $level = $this->stringValue($context, TelemetryKey::LEVEL);
        $logContext = $context[TelemetryKey::CONTEXT] ?? [];

        $formattedContext = null;
        if (is_array($logContext) && $logContext !== []) {
            $formattedContext = [];
            foreach ($logContext as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $formattedContext[$key] = $this->getDataFormatter()->formatVar($value);
            }
        }

        return [
            'message' => $message,
            'message_html' => null,
            'message_json' => null,
            'is_string' => true,
            'context' => $formattedContext,
            'context_json' => null,
            'label' => $level !== '' ? $level : 'info',
            'time' => $item->getStartedAt() ?? microtime(true),
        ];
    }

    /**
     * @param array<mixed> $context
     */
    private function stringValue(array $context, string $key): string
    {
        $value = $context[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }
}
