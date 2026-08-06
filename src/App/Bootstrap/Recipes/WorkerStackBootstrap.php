<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Foundation\TypedConfig;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Stack\ConceptStack;
use League\Container\Container;
use League\Event\ListenerSubscriber;

/**
 * Background worker / pipeline: DB + events + telemetry + logging. No HTTP/view.
 *
 * Entry: bin/worker.php (or similar) — not the web front controller.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class WorkerStackBootstrap
{
    public function boot(Container $container): void
    {
        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);
        $typed = new TypedConfig($config);

        $stack = ConceptStack::create();

        $stack->withMasking()
            ->patterns($typed->stringMap(ConfigKey::MASKING_PATTERNS))
            ->keyPatterns($typed->stringList(ConfigKey::MASKING_KEY_PATTERNS))
            ->rules($typed->classList(ConfigKey::MASKING_RULES, DataMaskerRuleInterface::class));

        $stack->withLogging()
            ->level($config->getString(ConfigKey::LOG_LEVEL, 'INFO'))
            ->channel($config->getString(ConfigKey::LOG_NAME, 'worker'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'worker.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 14),
            )
            ->withMasking();

        if ($config->getBool(ConfigKey::EVENTS_ENABLED)) {
            $stack->withEvents()
                ->subscribers($typed->classList(ConfigKey::EVENTS_SUBSCRIBERS, ListenerSubscriber::class));
        }

        $stack->withTelemetry()
            ->enabled($config->getBool(ConfigKey::TELEMETRY_ENABLED))
            ->logs($config->getBool(ConfigKey::TELEMETRY_LOGS))
            ->eventName(TelemetryEvent::LOG_RECORDED);

        $database = $stack->withDatabase()
            ->connection([
                'driver' => $config->getString(ConfigKey::DB_DRIVER),
                'host' => $config->getString(ConfigKey::DB_HOST),
                'port' => $config->getInt(ConfigKey::DB_PORT, 3306),
                'database' => $config->getString(ConfigKey::DB_DATABASE),
                'username' => $config->getString(ConfigKey::DB_USERNAME),
                'password' => $config->getString(ConfigKey::DB_PASSWORD),
                'charset' => $config->getString(ConfigKey::DB_CHARSET, 'utf8mb4'),
                'collation' => $config->getString(ConfigKey::DB_COLLATION, 'utf8mb4_unicode_ci'),
                'prefix' => $config->getString(ConfigKey::DB_PREFIX),
            ])
            ->withMasking();

        if ($config->getBool(ConfigKey::TELEMETRY_DB_QUERIES)) {
            $database->withEmitQueryEvents();
        }

        // Uncaught errors: EarlyErrorHandlingBootstrap (no HTTP renderer brick).

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
