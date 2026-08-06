<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Foundation\TypedConfig;
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
        $typed = new TypedConfig($config);
        $debug = $config->getBool(ConfigKey::APP_DEBUG);

        $stack = ConceptStack::create();

        $stack->withMasking()
            ->patterns($typed->stringMap(ConfigKey::MASKING_PATTERNS))
            ->keyPatterns($typed->stringList(ConfigKey::MASKING_KEY_PATTERNS))
            ->rules($typed->classList(ConfigKey::MASKING_RULES, DataMaskerRuleInterface::class));

        $stack->withLogging()
            ->level($config->getString(ConfigKey::LOG_LEVEL, 'ERROR'))
            ->channel($config->getString(ConfigKey::LOG_NAME, 'api'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'api.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
            )
            ->withMasking();

        $stack->withCasting()
            ->cacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::CASTER_CACHE_DIR, 'valinor')))
            ->debug($debug);

        $stack->withValidation()
            ->customRules($typed->classMap(ConfigKey::VALIDATOR_RULES, RuleInterface::class))
            ->globalExcept($typed->stringList(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT))
            ->withMasking();

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
            ->migrations($pathManager->rootList($typed->stringList(ConfigKey::MIGRATIONS_PATHS)))
            ->migrationsTable($config->getString(ConfigKey::MIGRATIONS_TABLE, 'migrations'))
            ->withMasking();

        if ($config->getBool(ConfigKey::DB_LOG_ENABLED)) {
            $database->withQueryLogging(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::DB_LOG_FILE, 'query.log')),
                $config->getInt(ConfigKey::DB_LOG_MAX_FILES, 7),
            );
        }

        $stack->withHttp()
            ->routes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->interceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $stack->withConsole()
            ->name($config->getString(ConfigKey::APP_NAME, 'API'))
            ->version($config->getString(ConfigKey::APP_VERSION, '1.0.0'));

        $stack->withErrorHandling()
            ->debug($debug)
            ->reportToLogs()
            ->renderJson();

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
