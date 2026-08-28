<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Foundation\TypedConfig;
use Concept\App\View\Twig\TwigAppExtension;
use Concept\Core\Container\ContainerDependency;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Stack\ConceptStack;
use League\Container\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Whoops\Handler\PrettyPageHandler;

/**
 * Admin UI + JSON API sharing one stack: session/views for web, FormRequests for both,
 * routes split in config (web.php + api.php). Errors: HTML page (JSON still via Accept).
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class AdminApiStackBootstrap
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

        $stack->addLogging()
            ->setLevel($config->getString(ConfigKey::LOG_LEVEL, 'ERROR'))
            ->setChannel($config->getString(ConfigKey::LOG_NAME, 'admin'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'app.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
            )
            ->withMasking();

        $stack->addCasting()
            ->setCacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::CASTER_CACHE_DIR, 'valinor')))
            ->setDebug($debug);

        $stack->addValidation()
            ->setRules($typed->classMap(ConfigKey::VALIDATOR_RULES, RuleInterface::class))
            ->setGlobalExcept($typed->stringList(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT))
            ->withMasking();

        $stack->addDatabase()
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
            ->withMasking();

        $stack->addSession()
            ->setOptions([
                'cookie_lifetime' => $config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME),
                'cookie_path' => $config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
                'cookie_secure' => $config->getBool(ConfigKey::SESSION_COOKIE_SECURE),
                'cookie_httponly' => $config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
                'cookie_samesite' => $config->getString(ConfigKey::SESSION_COOKIE_SAMESITE, 'Lax'),
            ])
            ->setHandler(new NativeFileSessionHandler())
            ->withCsrf();

        // routes.list typically: ['routes/web.php', 'routes/api.php'] — middleware differs per file
        $stack->addHttp()
            ->setRoutes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->setInterceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $stack->addView()
            ->setPaths($pathManager->rootMap($typed->stringMap(ConfigKey::VIEW_PATHS)))
            ->setExtensions([TwigAppExtension::class])
            ->setRouteNamespaces($typed->stringMap(ConfigKey::VIEW_ROUTE_NAMESPACE))
            ->withTwig()
            ->setViewsPath($pathManager->get(PathName::VIEWS))
            ->setCacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views')))
            ->setDebug($debug);

        $stack->addConsole()
            ->setName($config->getString(ConfigKey::APP_NAME, 'Admin'))
            ->setVersion($config->getString(ConfigKey::APP_VERSION, '1.0.0'))
            ->setCommands($typed->classList(ConfigKey::COMMANDS, Command::class));

        $stack->addErrorHandling()
            ->setDebug($debug, new PrettyPageHandler())
            ->setLogReporting()
            ->withViewRenderer($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
