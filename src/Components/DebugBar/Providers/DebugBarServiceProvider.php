<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Providers;

use Concept\Components\DebugBar\Support\ConfigDataCollector;
use Concept\Components\DebugBar\Support\CustomDebugBar;
use Concept\Components\DebugBar\Support\DatabaseDataCollector;
use Concept\Components\DebugBar\Support\RouteDataCollector;
use Concept\Components\DebugBar\Support\ServicesDataCollector;
use Concept\Components\DebugBar\Support\TimelineDataCollector;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\Http\Routing\RouteDescriptor;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use Concept\App\Telemetry\TelemetryCollector;
use DebugBar\JavascriptRenderer;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

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
            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);
            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);
            /** @var TelemetryCollector $telemetryCollector */
            $telemetryCollector = $container->get(TelemetryCollector::class);
            /** @var DataMaskerInterface $masker */
            $masker = $container->get(DataMaskerInterface::class);

            $debugBar = new CustomDebugBar($request, $session, $telemetryCollector, $masker);

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            /** @var RouteDescriptor $routeDescriptor */
            $routeDescriptor = $container->get(RouteDescriptor::class);

            $debugBar->addCollector(new DatabaseDataCollector($telemetryCollector));
            $debugBar->addCollector(new RouteDataCollector($telemetryCollector, $routeDescriptor));
            $debugBar->addCollector(new ServicesDataCollector($telemetryCollector));
            $debugBar->addCollector(new TimelineDataCollector($telemetryCollector));
            $debugBar->addCollector(new ConfigDataCollector($config, $masker));

            return $debugBar;
        })->setShared(true);

        $container->add(JavascriptRenderer::class, function () use ($container) {
            /** @var CustomDebugBar $debugBar */
            $debugBar = $container->get(CustomDebugBar::class);
            $debugBarRenderer = $debugBar->getJavascriptRenderer();
            $debugBarRenderer->setBaseUrl( self::ASSETS_DIR);

            return $debugBarRenderer;
        })->setShared(true);
    }
}
