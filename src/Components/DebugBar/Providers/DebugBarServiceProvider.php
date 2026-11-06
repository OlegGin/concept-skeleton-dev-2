<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Providers;

use Concept\Components\DebugBar\Support\ConfigDataCollector;
use Concept\Components\DebugBar\Support\CustomDebugBar;
use Concept\Components\DebugBar\Support\DatabaseDataCollector;
use Concept\Components\DebugBar\Support\ExtensionsDataCollector;
use Concept\Components\DebugBar\Support\RouteDataCollector;
use Concept\Components\DebugBar\Support\ComponentsDataCollector;
use Concept\Components\DebugBar\Support\TimelineDataCollector;
use Concept\Core\Container\ContainerDependency;
use Concept\Core\Http\Contracts\RequestContextInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\Http\Routing\RouteDescriptor;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use Concept\Extensions\Telemetry\TelemetryCollector;
use DebugBar\JavascriptRenderer;
use League\Container\ServiceProvider\AbstractServiceProvider;

class DebugBarServiceProvider extends AbstractServiceProvider
{
    private const ASSETS_DIR = '/components/debug-bar';

    public function provides(string $id): bool
    {
        $services = [
            CustomDebugBar::class,
            JavascriptRenderer::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(CustomDebugBar::class, function () use ($container) {
            $debugBar = new CustomDebugBar(
                ContainerDependency::get($container, RequestContextInterface::class),
                ContainerDependency::get($container, SessionInterface::class),
                ContainerDependency::get($container, TelemetryCollector::class),
                ContainerDependency::get($container, DataMaskerInterface::class),
            );

            $config = ContainerDependency::get($container, ConfigInterface::class);
            $routeDescriptor = ContainerDependency::get($container, RouteDescriptor::class);

            $debugBar->addCollector(new DatabaseDataCollector(
                ContainerDependency::get($container, TelemetryCollector::class),
            ));
            $debugBar->addCollector(new RouteDataCollector(
                ContainerDependency::get($container, TelemetryCollector::class),
                $routeDescriptor,
            ));
            $debugBar->addCollector(new ComponentsDataCollector(
                ContainerDependency::get($container, TelemetryCollector::class),
            ));
            $debugBar->addCollector(new ExtensionsDataCollector(
                ContainerDependency::get($container, TelemetryCollector::class),
            ));
            $debugBar->addCollector(new TimelineDataCollector(
                ContainerDependency::get($container, TelemetryCollector::class),
            ));
            $debugBar->addCollector(new ConfigDataCollector(
                $config,
                ContainerDependency::get($container, DataMaskerInterface::class),
            ));

            return $debugBar;
        })->setShared(true);

        $container->add(JavascriptRenderer::class, function () use ($container) {
            $debugBar = ContainerDependency::get($container, CustomDebugBar::class);
            $debugBarRenderer = $debugBar->getJavascriptRenderer();
            $debugBarRenderer->setBaseUrl(self::ASSETS_DIR);

            return $debugBarRenderer;
        })->setShared(true);
    }
}
