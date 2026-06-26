<?php declare(strict_types=1);

namespace Concept\Components\Telemetry\Providers;

use Concept\App\Foundation\ConfigKey;
use Concept\Components\Telemetry\Subscribers\ComponentsTelemetrySubscriber;
use Concept\Components\Telemetry\Subscribers\DatabaseTelemetrySubscriber;
use Concept\Components\Telemetry\Subscribers\FormRequestTelemetrySubscriber;
use Concept\Components\Telemetry\Subscribers\ViewTelemetrySubscriber;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Event\EventServiceProvider;
use Concept\Extensions\LoggerMonolog\LogHandlerRegistry;
use Concept\Extensions\Telemetry\Handlers\TelemetryLogHandler;
use Concept\Extensions\Telemetry\Subscribers\TelemetryEventSubscriber;
use Concept\Extensions\Telemetry\Subscribers\TelemetrySubscriberFactory;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Concept\Extensions\Telemetry\TelemetryServiceProvider as TelemetryExtensionServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Event\ListenerSubscriber;

final class TelemetryServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /** @var list<class-string<ListenerSubscriber>> */
    private const array SUBSCRIBERS = [
        TelemetryEventSubscriber::class,
        DatabaseTelemetrySubscriber::class,
        FormRequestTelemetrySubscriber::class,
        ViewTelemetrySubscriber::class,
        ComponentsTelemetrySubscriber::class,
    ];

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

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        if (!$config->getBool(ConfigKey::TELEMETRY_ENABLED)) {
            return;
        }

        if ($config->getBool(ConfigKey::TELEMETRY_LOGS)) {
            $container->add(TelemetryLogHandler::class, function() use ($container): TelemetryLogHandler {
                /** @var TelemetryCollector $collector */
                $collector = $container->get(TelemetryCollector::class);

                return new TelemetryLogHandler($collector);
            })->setShared(true);

            if ($container->has(LogHandlerRegistry::class)) {
                /** @var LogHandlerRegistry $registry */
                $registry = $container->get(LogHandlerRegistry::class);
                $registry->add(TelemetryLogHandler::class);
            }
        }

        if (!$config->getBool(ConfigKey::EVENTS_ENABLED)) {
            return;
        }

        $subscriberClasses = $this->subscriberClasses($config);

        foreach ($subscriberClasses as $subscriberClass) {
            $container->add($subscriberClass, function() use ($container, $subscriberClass): ListenerSubscriber {
                /** @var TelemetryCollector $collector */
                $collector = $container->get(TelemetryCollector::class);

                return TelemetrySubscriberFactory::create($subscriberClass, $collector);
            })->setShared(true);
        }

        $container->addServiceProvider(new EventServiceProvider($subscriberClasses));
    }

    /**
     * @return list<class-string<ListenerSubscriber>>
     */
    private function subscriberClasses(ConfigInterface $config): array
    {
        /** @var list<class-string<ListenerSubscriber>> $extra */
        $extra = $config->get(ConfigKey::EVENTS_SUBSCRIBERS) ?? [];

        return array_values(array_unique([...self::SUBSCRIBERS, ...$extra]));
    }
}
