<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\AppConfig;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
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

        if ($config->getBool(ConfigKey::EVENTS_ENABLED)) {
            $stack->addEvents()
                ->setSubscribers($config->getClassList(ConfigKey::EVENTS_SUBSCRIBERS, ListenerSubscriber::class));
        }

        $stack->addTelemetry()
            ->setEnabled($config->getBool(ConfigKey::TELEMETRY_ENABLED))
            ->setLogs($config->getBool(ConfigKey::TELEMETRY_LOGS))
            ->setEventName(TelemetryEvent::LOG_RECORDED);

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
            ->setSeeders($config->getClassStringList(ConfigKey::SEEDERS_LIST))
            ->withMasking();

        if ($config->getBool(ConfigKey::TELEMETRY_DB_QUERIES)) {
            $database->withEmitQueryEvents();
        }

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
            ->setName($config->getString(ConfigKey::APP_NAME, 'SaaS'))
            ->setVersion($config->getString(ConfigKey::APP_VERSION, '1.0.0'))
            ->setCommands($config->getClassList(ConfigKey::COMMANDS, Command::class));

        $stack->addErrorHandling()
            ->setDebug($debug, new PrettyPageHandler())
            ->setLogReporting()
            ->withViewRenderer($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

        $components = $stack->addComponents()
            ->setClasses($config->getClassList(ConfigKey::COMPONENTS, ComponentInterface::class))
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
