<?php declare(strict_types=1);

namespace Concept\Components\Telemetry\Providers;

use Concept\App\Foundation\ConfigKey;
use Concept\Components\Telemetry\Support\EventSubscriberCollector;
use Concept\Components\Telemetry\TelemetryComponent;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Event\EventServiceProvider;
use Concept\Extensions\Telemetry\Handlers\TelemetryLogHandler;
use Concept\Extensions\Telemetry\Subscribers\TelemetrySubscriberFactory;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryServiceProvider as TelemetryExtensionServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Event\ListenerSubscriber;

final class TelemetryServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private static bool $registered = false;
    private static bool $booted = false;

    public function provides(string $id): bool
    {
        return $id === TelemetryLogHandler::class;
    }

    public function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        $this->getContainer()->addServiceProvider(new TelemetryExtensionServiceProvider());
    }

    public function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        $container = $this->getContainer();

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        if (!$this->isEnabled($config)) {
            return;
        }

        if ($config->getBool(ConfigKey::TELEMETRY_LOGS)) {
            $container->add(TelemetryLogHandler::class, function() use ($container): TelemetryLogHandler {
                /** @var TelemetryCollector $collector */
                $collector = $container->get(TelemetryCollector::class);

                return new TelemetryLogHandler($collector);
            })->setShared(true);
        }

        if (!$config->getBool(ConfigKey::EVENTS_ENABLED)) {
            return;
        }

        $subscriberClasses = EventSubscriberCollector::collect($config);

        foreach ($subscriberClasses as $subscriberClass) {
            $container->add($subscriberClass, function() use ($container, $subscriberClass): ListenerSubscriber {
                /** @var TelemetryCollector $collector */
                $collector = $container->get(TelemetryCollector::class);

                return TelemetrySubscriberFactory::create($subscriberClass, $collector);
            })->setShared(true);
        }

        $container->addServiceProvider(new EventServiceProvider($subscriberClasses));
    }

    private function isEnabled(ConfigInterface $config): bool
    {
        if (!$config->getBool(ConfigKey::TELEMETRY_ENABLED)) {
            return false;
        }

        /** @var array<class-string, class-string> $components */
        $components = $config->get(ConfigKey::COMPONENTS) ?? [];

        return isset($components[TelemetryComponent::class]);
    }
}
