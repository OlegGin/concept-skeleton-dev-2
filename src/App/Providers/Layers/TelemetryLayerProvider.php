<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Event\EventServiceProvider;
use Concept\Extensions\LoggerMonolog\LogHandlerRegistry;
use Concept\Extensions\Telemetry\Handlers\TelemetryLogHandler;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryServiceProvider as TelemetryExtensionServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Event\ListenerSubscriber;

final class TelemetryLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return $id === TelemetryLogHandler::class;
    }

    public function register(): void
    {
        $this->getContainer()->addServiceProvider(new TelemetryExtensionServiceProvider());
    }

    public function boot(): void
    {
        $container = $this->getContainer();
        $config = ContainerDependency::get($container, ConfigInterface::class);

        if (!$config->getBool(ConfigKey::TELEMETRY_ENABLED)) {
            return;
        }

        if ($config->getBool(ConfigKey::TELEMETRY_LOGS)) {
            $container->add(TelemetryLogHandler::class, function() use ($container): TelemetryLogHandler {
                return new TelemetryLogHandler(
                    collector: ContainerDependency::get($container, TelemetryCollector::class),
                    eventName: TelemetryEvent::LOG_RECORDED,
                );
            })->setShared(true);

            if ($container->has(LogHandlerRegistry::class)) {
                $registry = ContainerDependency::get($container, LogHandlerRegistry::class);
                $registry->add(TelemetryLogHandler::class);
            }
        }

        if (!$config->getBool(ConfigKey::EVENTS_ENABLED)) {
            return;
        }

        /** @var list<class-string<ListenerSubscriber>> $subscriberClasses */
        $subscriberClasses = $config->getArray(ConfigKey::EVENTS_SUBSCRIBERS);

        $container->addServiceProvider(new EventServiceProvider($subscriberClasses));
    }
}
