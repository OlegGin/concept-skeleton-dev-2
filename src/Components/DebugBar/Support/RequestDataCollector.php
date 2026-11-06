<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use Concept\Extensions\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\Core\Http\Contracts\RequestContextInterface;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

final class RequestDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'request';

    public function __construct(
        private readonly RequestContextInterface $requestContext,
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
        $request = $this->requestContext->current();
        if ($request === null) {
            return [
                'data' => [],
                'tooltip' => null,
                'badge' => null,
            ];
        }

        $data = [
            'Path' => $request->getUri()->getPath(),
            'Method' => $request->getMethod(),
            'View' => $this->getTplRenderInfo(),
            'QueryParams' => $this->masker->mask($request->getQueryParams()),
            'Cookies' => $this->masker->mask($request->getCookieParams()),
            'Session' => $this->masker->mask($this->session->all()),
            'Uri' => $request->getUri(),
            'Attributes' => $request->getAttributes(),
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
