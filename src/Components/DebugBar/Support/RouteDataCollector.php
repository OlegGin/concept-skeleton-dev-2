<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Core\Http\Routing\RouteDescriptor;
use Concept\Core\Services\Telemetry\Contracts\TelemetryItemInterface;
use Concept\Core\Services\Telemetry\TelemetryCollector;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use Concept\Core\Services\Telemetry\TelemetryKey;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use League\Route\Route;

final class RouteDataCollector extends DataCollector implements Renderable
{
    use DataFormaterTrait;

    private const string NAME = 'route';

    public function __construct(
        private readonly TelemetryCollector $telemetryCollector,
        private readonly RouteDescriptor $routeDescriptor,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{data: array<string, mixed>, badge: string|null}
     */
    public function collect(): array
    {
        $telemetryItems = $this->telemetryCollector->items(TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED);
        if ($telemetryItems === []) {
            return [
                'data' => [],
                'badge' => null,
            ];
        }

        /** @var TelemetryItemInterface $telemetryItem */
        $telemetryItem = array_first($telemetryItems);
        $route = $telemetryItem->getContext()[TelemetryKey::ROUTE] ?? null;
        if (!$route instanceof Route) {
            return [
                'data' => [],
                'badge' => null,
            ];
        }

        $description = $this->routeDescriptor->describe($route, true);
        $strategy = $route->getStrategy();

        $data = [
            'Method' => $description['method'],
            'Path' => $description['path'],
            'Name' => $description['name'],
            'Action' => $description['action'],
            'Middleware' => $description['middleware'],
            'GroupPrefix' => $description['group_prefix'],
            'Vars' => $description['vars'],
            'ResolvedPath' => $route->getPath($description['vars']),
            'Host' => $route->getHost(),
            'Port' => $route->getPort(),
            'Scheme' => $route->getScheme(),
            'Strategy' => $strategy !== null ? $strategy::class : null,
            'Interceptors' => $this->collectInterceptors(),
            'StartedAt' => $this->formatStartedAt($telemetryItem->getStartedAt()),
            'Duration' => $this->formatDuration($telemetryItem->getDuration()),
        ];

        foreach ($data as $name => $value) {
            $data[$name] = $this->getDataFormatter()->formatVar($value);
        }

        return [
            'data' => $data,
            'badge' => $description['name'],
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
                'icon' => 'map',
                'widget' => $widget,
                'map' => self::NAME . '.data',
                'default' => '{}',
            ],
            self::NAME . ':badge' => [
                'map' => self::NAME . '.badge',
                'default' => 'null',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function collectInterceptors(): array
    {
        $interceptors = [];
        $telemetryItems = $this->telemetryCollector->items(TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED);

        foreach ($telemetryItems as $telemetryItem) {
            /** @var TelemetryItemInterface $telemetryItem */
            $interceptors[] = $this->getContextAttribute($telemetryItem, TelemetryKey::NAME);
        }

        return $interceptors;
    }
}
