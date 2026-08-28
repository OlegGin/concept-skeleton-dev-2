<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Foundation\TypedConfig;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\App\View\Twig\TwigAppExtension;
use Concept\Core\Container\ContainerDependency;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Stack\ConceptStack;
use League\Container\Container;
use League\Event\ListenerSubscriber;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Whoops\Handler\PrettyPageHandler;

/**
 * Builds ConceptStack from PathManager + Config and registers its providers.
 * Requires FoundationBootstrap booted first.
 */
final class ApplicationStackBootstrapBackup
{
    public function boot(Container $container): void
    {
        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);
        $typed = new TypedConfig($config);

        $debug = $config->getBool(ConfigKey::APP_DEBUG);
        $stack = ConceptStack::create();

        $stack->addMasking()
            ->setPatterns($typed->stringMap(ConfigKey::MASKING_PATTERNS))
            ->setKeyPatterns($typed->stringList(ConfigKey::MASKING_KEY_PATTERNS))
            ->setRules($typed->classList(ConfigKey::MASKING_RULES, DataMaskerRuleInterface::class));

        $appLogFileName = $config->getString(ConfigKey::LOG_FILE, 'app.log');
        $stack->addLogging()
            ->setLevel($config->getString(ConfigKey::LOG_LEVEL, 'ERROR'))
            ->setChannel($config->getString(ConfigKey::LOG_NAME, 'app'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $appLogFileName),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
            )
            ->withMasking();

        if ($config->getBool(ConfigKey::EVENTS_ENABLED)) {
            $stack->addEvents()
                ->setSubscribers($typed->classList(ConfigKey::EVENTS_SUBSCRIBERS, ListenerSubscriber::class));
        }

        $stack->addTelemetry()
            ->setEnabled($config->getBool(ConfigKey::TELEMETRY_ENABLED))
            ->setLogs($config->getBool(ConfigKey::TELEMETRY_LOGS))
            ->setEventName(TelemetryEvent::LOG_RECORDED);

        $casterCacheDir = $config->getString(ConfigKey::CASTER_CACHE_DIR, 'valinor');
        $stack->addCasting()
            ->setTransformers($typed->classStringList(ConfigKey::CASTER_TRANSFORMERS))
            ->setCacheDir($pathManager->get(PathName::CACHE, $casterCacheDir))
            ->setDebug($debug);

        $validationLogFileName = $config->getString(ConfigKey::VALIDATOR_LOG_FILE, 'validation.log');
        $validation = $stack->addValidation()
            ->setRules($typed->classMap(ConfigKey::VALIDATOR_RULES, RuleInterface::class))
            ->setGlobalExcept($typed->stringList(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT));
        if ($config->getBool(ConfigKey::VALIDATOR_LOG_ENABLED)) {
            $validation->withLogging(
                $pathManager->get(PathName::LOGS, $validationLogFileName),
                $config->getInt(ConfigKey::VALIDATOR_LOG_MAX_FILES, 7),
            );
        }
        $validation->withMasking();

        $database = $stack->addDatabase()
            ->setConnection([
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
            ->setMigrations($pathManager->rootList($typed->stringList(ConfigKey::MIGRATIONS_PATHS)))
            ->setMigrationsTable($config->getString(ConfigKey::MIGRATIONS_TABLE, 'migrations'))
            ->setSeeders($typed->classStringList(ConfigKey::SEEDERS_LIST))
            ->withMasking();

        if ($config->getBool(ConfigKey::DB_LOG_ENABLED)) {
            $queryLogFileName = $config->getString(ConfigKey::DB_LOG_FILE, 'query.log');
            $database->withQueryLogging(
                $pathManager->get(PathName::LOGS, $queryLogFileName),
                $config->getInt(ConfigKey::DB_LOG_MAX_FILES, 7),
            );
        }

        if ($config->getBool(ConfigKey::TELEMETRY_DB_QUERIES)) {
            $database->withEmitQueryEvents();
        }

        $sessionFilePath = $config->get(ConfigKey::SESSION_FILE_PATH);
        $sessionHandler = is_string($sessionFilePath) && $sessionFilePath !== ''
            ? new NativeFileSessionHandler($pathManager->root($sessionFilePath))
            : new NativeFileSessionHandler();

        $stack->addSession()
            ->setOptions([
                'cookie_lifetime' => $config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME),
                'cookie_path' => $config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
                'cookie_secure' => $config->getBool(ConfigKey::SESSION_COOKIE_SECURE),
                'cookie_httponly' => $config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
                'cookie_domain' => $config->getString(ConfigKey::SESSION_COOKIE_DOMAIN),
                'cookie_samesite' => $config->getString(ConfigKey::SESSION_COOKIE_SAMESITE, 'Lax'),
                'use_only_cookies' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_ONLY_COOKIES, true),
                'use_strict_mode' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_STRICT_MODE, true),
            ])
            ->setHandler($sessionHandler)
            ->withCsrf();

        $stack->addHttp()
            ->setRoutes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->setInterceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $viewExtensions = $typed->classStringList(ConfigKey::VIEW_EXTENSIONS);
        if ($viewExtensions === []) {
            $viewExtensions = [TwigAppExtension::class];
        }

        $viewCacheDir = $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views');
        $stack->addView()
            ->setPaths($pathManager->rootMap($typed->stringMap(ConfigKey::VIEW_PATHS)))
            ->setExtensions($viewExtensions)
            ->setRouteNamespaces($typed->stringMap(ConfigKey::VIEW_ROUTE_NAMESPACE))
            ->withTwig()
            ->setViewsPath($pathManager->get(PathName::VIEWS))
            ->setCacheDir($pathManager->get(PathName::CACHE, $viewCacheDir))
            ->setDebug($debug);

        $stack->addConsole()
            ->setName($config->getString(ConfigKey::APP_NAME, 'Concept Skeleton'))
            ->setVersion($config->getString(ConfigKey::APP_VERSION, '1.0.0'))
            ->setCommands($typed->classList(ConfigKey::COMMANDS, Command::class));

        $stack->addErrorHandling()
            ->setDebug($debug, new PrettyPageHandler())
            ->setLogReporting()
            ->withViewRenderer($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

        $components = $stack->addComponents()
            ->setClasses($typed->classList(ConfigKey::COMPONENTS, ComponentInterface::class))
            ->withDatabase()
            ->withConsole()
            ->withHttp();

        if (PHP_SAPI !== 'cli') {
            $components->withView();
        }

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
