<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

final class ConfigDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'config';

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly DataMaskerInterface $masker,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{data: array<string, mixed>, count: int}
     */
    public function collect(): array
    {
        $masked = $this->masker->mask($this->config->all());
        if (!is_array($masked)) {
            return [
                'data' => [],
                'count' => 0,
            ];
        }

        $data = [];
        foreach ($masked as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $data[$name] = $this->getDataFormatter()->formatVar($value);
        }

        return [
            'data' => $data,
            'count' => count($data),
        ];
    }

    /**
     * @return array<string, array{icon?: string, widget?: string, map?: string, default?: mixed}>
     */
    public function getWidgets(): array
    {
        $widget = match (true) {
            $this->isJsonVarDumperUsed() => 'PhpDebugBar.Widgets.JsonVariableListWidget',
            $this->isHtmlVarDumperUsed() => 'PhpDebugBar.Widgets.HtmlVariableListWidget',
            default => 'PhpDebugBar.Widgets.VariableListWidget',
        };

        return [
            self::NAME => [
                'icon' => 'cog',
                'widget' => $widget,
                'map' => self::NAME . '.data',
                'default' => '{}',
            ],
            self::NAME . ':badge' => [
                'map' => self::NAME . '.count',
                'default' => 0,
            ],
        ];
    }
}
