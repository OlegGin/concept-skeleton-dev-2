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
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Event\ListenerSubscriber;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

/**
 * Builds ConceptStack from PathManager + Config and registers its providers.
 * Requires FoundationBootstrap registered first.
 */
final class ApplicationStackBootstrap extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $container = $this->getContainer();
        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);
        $typed = new TypedConfig($config);

        $debug = $config->getBool(ConfigKey::APP_DEBUG);
        $stack = ConceptStack::create();

        $stack->withMasking()
            ->patterns($typed->stringMap(ConfigKey::MASKING_PATTERNS))
            ->keyPatterns($typed->stringList(ConfigKey::MASKING_KEY_PATTERNS))
            ->rules($typed->classList(ConfigKey::MASKING_RULES, DataMaskerRuleInterface::class));

        $appLogFileName = $config->getString(ConfigKey::LOG_FILE, 'app.log');
        $stack->withLogging()
            ->level($config->getString(ConfigKey::LOG_LEVEL, 'ERROR'))
            ->channel($config->getString(ConfigKey::LOG_NAME, 'app'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $appLogFileName),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
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

        $casterCacheDir = $config->getString(ConfigKey::CASTER_CACHE_DIR, 'valinor');
        $stack->withCasting()
            ->transformers($typed->classStringList(ConfigKey::CASTER_TRANSFORMERS))
            ->cacheDir($pathManager->get(PathName::CACHE, $casterCacheDir))
            ->debug($debug);

        $validationLogFileName = $config->getString(ConfigKey::VALIDATOR_LOG_FILE, 'validation.log');
        $stack->withValidation()
            ->customRules($typed->classMap(ConfigKey::VALIDATOR_RULES, RuleInterface::class))
            ->logEnabled($config->getBool(ConfigKey::VALIDATOR_LOG_ENABLED))
            ->logFilePath($pathManager->get(PathName::LOGS, $validationLogFileName))
            ->logMaxFiles($config->getInt(ConfigKey::VALIDATOR_LOG_MAX_FILES, 7))
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
            ->seeders($typed->classStringList(ConfigKey::SEEDERS_LIST))
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

        $stack->withSession()
            ->options([
                'cookie_lifetime' => $config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME),
                'cookie_path' => $config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
                'cookie_secure' => $config->getBool(ConfigKey::SESSION_COOKIE_SECURE),
                'cookie_httponly' => $config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
                'cookie_domain' => $config->getString(ConfigKey::SESSION_COOKIE_DOMAIN),
                'cookie_samesite' => $config->getString(ConfigKey::SESSION_COOKIE_SAMESITE, 'Lax'),
                'use_only_cookies' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_ONLY_COOKIES, true),
                'use_strict_mode' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_STRICT_MODE, true),
            ])
            ->handler($sessionHandler)
            ->withCsrf();

        $stack->withHttp()
            ->routes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->interceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $viewExtensions = $typed->classStringList(ConfigKey::VIEW_EXTENSIONS);
        if ($viewExtensions === []) {
            $viewExtensions = [TwigAppExtension::class];
        }

        $viewCacheDir = $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views');
        $stack->withView()
            ->paths($pathManager->rootMap($typed->stringMap(ConfigKey::VIEW_PATHS)))
            ->extensions($viewExtensions)
            ->routeNamespace($typed->stringMap(ConfigKey::VIEW_ROUTE_NAMESPACE))
            ->withTwig()
            ->viewsPath($pathManager->get(PathName::VIEWS))
            ->cacheDir($pathManager->get(PathName::CACHE, $viewCacheDir))
            ->debug($debug);

        $stack->withConsole()
            ->name($config->getString(ConfigKey::APP_NAME, 'Concept Skeleton'))
            ->version($config->getString(ConfigKey::APP_VERSION, '1.0.0'))
            ->commands($typed->classList(ConfigKey::COMMANDS, Command::class));

        $stack->withErrorHandling()
            ->debug($debug)
            ->showDebugExceptionPage()
            ->reportToLog()
            ->renderHtmlErrorPage($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

        $components = $stack->withComponents()
            ->classes($typed->classList(ConfigKey::COMPONENTS, ComponentInterface::class))
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
