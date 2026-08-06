<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

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
 * SaaS-shaped slice: full web stack + components (ACL/Auth…) + events + telemetry.
 * Closest to the live ApplicationStackBootstrap recipe.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class SaasStackBootstrap
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
            ->channel($config->getString(ConfigKey::LOG_NAME, 'app'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'app.log')),
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
            ->seeders($typed->classStringList(ConfigKey::SEEDERS_LIST))
            ->withMasking();

        if ($config->getBool(ConfigKey::TELEMETRY_DB_QUERIES)) {
            $database->withEmitQueryEvents();
        }

        $stack->withSession()
            ->options([
                'cookie_lifetime' => $config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME),
                'cookie_path' => $config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
                'cookie_secure' => $config->getBool(ConfigKey::SESSION_COOKIE_SECURE),
                'cookie_httponly' => $config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
                'cookie_samesite' => $config->getString(ConfigKey::SESSION_COOKIE_SAMESITE, 'Lax'),
            ])
            ->handler(new NativeFileSessionHandler())
            ->withCsrf();

        $stack->withHttp()
            ->routes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->interceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $stack->withView()
            ->paths($pathManager->rootMap($typed->stringMap(ConfigKey::VIEW_PATHS)))
            ->extensions([TwigAppExtension::class])
            ->routeNamespace($typed->stringMap(ConfigKey::VIEW_ROUTE_NAMESPACE))
            ->withTwig()
            ->viewsPath($pathManager->get(PathName::VIEWS))
            ->cacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views')))
            ->debug($debug);

        $stack->withConsole()
            ->name($config->getString(ConfigKey::APP_NAME, 'SaaS'))
            ->version($config->getString(ConfigKey::APP_VERSION, '1.0.0'))
            ->commands($typed->classList(ConfigKey::COMMANDS, Command::class));

        $stack->withErrorHandling()
            ->debug($debug, new PrettyPageHandler())
            ->reportToLogs()
            ->renderView($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

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
