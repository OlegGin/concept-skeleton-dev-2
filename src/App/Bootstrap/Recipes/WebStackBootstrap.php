<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\AppConfig;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
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
 * Classic web app with DB: views, session/CSRF, forms, Eloquent, console.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class WebStackBootstrap
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
            ->setChannel($config->getString(ConfigKey::LOG_NAME, 'app'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'app.log')),
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

        $stack->addDatabase()
            ->setConnection($app->dbConnection())
            ->setMigrations($pathManager->rootList($config->getStringList(ConfigKey::MIGRATIONS_PATHS)))
            ->setMigrationsTable($config->getString(ConfigKey::MIGRATIONS_TABLE, 'migrations'))
            ->withMasking();

        $stack->addSession()
            ->setOptions($app->sessionCookieOptions())
            ->setHandlerFactory(fn(): NativeFileSessionHandler => new NativeFileSessionHandler())
            ->withCsrf();

        $stack->addHttp()
            ->setRoutes($pathManager->rootList($config->getStringList(ConfigKey::ROUTES_LIST)))
            ->setInterceptors($config->getClassList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $stack->addView()
            ->setPaths($pathManager->rootMap($config->getStringMap(ConfigKey::VIEW_PATHS)))
            ->setExtensions([TwigAppExtension::class])
            ->setRouteNamespaces($config->getStringMap(ConfigKey::VIEW_ROUTE_NAMESPACE))
            ->withTwig()
            ->setViewsPath($pathManager->get(PathName::VIEWS))
            ->setCacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views')))
            ->setDebug($debug);

        $stack->addConsole()
            ->setName($config->getString(ConfigKey::APP_NAME, 'Web'))
            ->setVersion($config->getString(ConfigKey::APP_VERSION, '1.0.0'))
            ->setCommands($config->getClassList(ConfigKey::COMMANDS, Command::class));

        $stack->addErrorHandling()
            ->setDebug($debug, new PrettyPageHandler())
            ->setLogReporting()
            ->withViewRenderer($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
