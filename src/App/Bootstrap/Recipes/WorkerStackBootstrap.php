<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\AppConfig;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
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
        $app = new AppConfig($config);

        $stack = ConceptStack::create();

        $stack->addMasking()
            ->setPatterns($config->getStringMap(ConfigKey::MASKING_PATTERNS))
            ->setKeyPatterns($config->getStringList(ConfigKey::MASKING_KEY_PATTERNS))
            ->setRules($config->getClassList(ConfigKey::MASKING_RULES, DataMaskerRuleInterface::class));

        $stack->addLogging()
            ->setLevel($config->getString(ConfigKey::LOG_LEVEL, 'INFO'))
            ->setChannel($config->getString(ConfigKey::LOG_NAME, 'worker'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'worker.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 14),
            )
            ->withMasking();

        if ($config->getBool(ConfigKey::EVENTS_ENABLED)) {
            $stack->addEvents()
                ->setSubscribers($config->getClassList(ConfigKey::EVENTS_SUBSCRIBERS, ListenerSubscriber::class));
        }

        $stack->addTelemetry()
            ->setEnabled($config->getBool(ConfigKey::TELEMETRY_ENABLED))
            ->setLogs($config->getBool(ConfigKey::TELEMETRY_LOGS))
            ->setEventName(TelemetryEvent::LOG_RECORDED);

        $database = $stack->addDatabase()
            ->setConnection($app->dbConnection())
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
