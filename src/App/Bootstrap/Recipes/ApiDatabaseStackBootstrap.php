<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\AppConfig;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\Core\Container\ContainerDependency;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Stack\ConceptStack;
use League\Container\Container;

/**
 * JSON API with database: ApiStack + Eloquent/migrations. No session/view.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class ApiDatabaseStackBootstrap
{
    public function boot(Container $container): void
    {
        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);
        $app = new AppConfig($config);
        $debug = $config->getBool(ConfigKey::APP_DEBUG);

        $stack = ConceptStack::create();

        $stack->addMasking()
            ->setPatterns($config->getStringMap(ConfigKey::MASKING_PATTERNS))
            ->setKeyPatterns($config->getStringList(ConfigKey::MASKING_KEY_PATTERNS))
            ->setRules($config->getClassList(ConfigKey::MASKING_RULES, DataMaskerRuleInterface::class));

        $stack->addLogging()
            ->setLevel($config->getString(ConfigKey::LOG_LEVEL, 'ERROR'))
            ->setChannel($config->getString(ConfigKey::LOG_NAME, 'api'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'api.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
            )
            ->withMasking();

        $stack->addCasting()
            ->setCacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::CASTER_CACHE_DIR, 'valinor')))
            ->setDebug($debug);

        $stack->addValidation()
            ->setRules($config->getClassMap(ConfigKey::VALIDATOR_RULES, RuleInterface::class))
            ->setGlobalExcept($config->getStringList(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT))
            ->withMasking();

        $database = $stack->addDatabase()
            ->setConnection($app->dbConnection())
            ->setMigrations($pathManager->rootList($config->getStringList(ConfigKey::MIGRATIONS_PATHS)))
            ->setMigrationsTable($config->getString(ConfigKey::MIGRATIONS_TABLE, 'migrations'))
            ->withMasking();

        if ($config->getBool(ConfigKey::DB_LOG_ENABLED)) {
            $database->withQueryLogging(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::DB_LOG_FILE, 'query.log')),
                $config->getInt(ConfigKey::DB_LOG_MAX_FILES, 7),
            );
        }

        $stack->addHttp()
            ->setRoutes($pathManager->rootList($config->getStringList(ConfigKey::ROUTES_LIST)))
            ->setInterceptors($config->getClassList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $stack->addConsole()
            ->setName($config->getString(ConfigKey::APP_NAME, 'API'))
            ->setVersion($config->getString(ConfigKey::APP_VERSION, '1.0.0'));

        $stack->addErrorHandling()
            ->setDebug($debug)
            ->setLogReporting()
            ->withJsonRenderer();

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
