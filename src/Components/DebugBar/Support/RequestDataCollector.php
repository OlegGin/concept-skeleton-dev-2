<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Core\Services\DataMasker\Contracts\DataMaskerInterface;
use Concept\Core\Services\Session\Contracts\SessionInterface;
use Concept\Core\Services\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Core\Services\Telemetry\TelemetryCollector;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Psr\Http\Message\ServerRequestInterface;

final class RequestDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'request';

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly SessionInterface $session,
        private readonly TelemetryCollector $telemetryCollector,
        private readonly DataMaskerInterface $masker,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{data: array<string, mixed>, tooltip: array<string, mixed>|null, badge: string|null}
     */
    public function collect(): array
    {
        $data = [
            'Path' => $this->request->getUri()->getPath(),
            'Method' => $this->request->getMethod(),
            'View' => $this->getTplRenderInfo(),
            'QueryParams' => $this->masker->mask($this->request->getQueryParams()),
            'Cookies' => $this->masker->mask($this->request->getCookieParams()),
            'Session' => $this->masker->mask($this->session->all()),
            'Uri' => $this->request->getUri(),
            'Attributes' => $this->request->getAttributes(),
        ];

        foreach ($data as $name => $global) {
            $data[$name] = $this->getDataFormatter()->formatVar($global);
        }

        return [
            'data' => $data,
            'tooltip' => null,
            'badge' => null,
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

        $widgets = [
            'request' => [
                'icon' => 'arrows-left-right',
                'widget' => $widget,
                'map' => 'request.data',
                'default' => '{}',
            ],
            'request:badge' => [
                'map' => 'request.badge',
                'default' => 'null',
            ],
        ];

        $widgets['request_uri'] = [
            'icon' => 'share-3',
            'map' => 'request.data.uri',
            'link' => 'request',
            'default' => '',
        ];
        $widgets['request_uri:tooltip'] = [
            'map' => 'request.tooltip',
            'default' => '{}',
        ];

        return $widgets;
    }

    private function getTplRenderInfo(): string
    {
        $telemetryItems = $this->telemetryCollector->items(TelemetryEvent::TPL_RENDERED);
        if (empty($telemetryItems)) {
            return '';
        }

        /** @var TelemetryItemInterface $telemetryItem */
        $telemetryItem = reset($telemetryItems);

        return $this->getContextAttribute($telemetryItem, 'view');
    }
}
