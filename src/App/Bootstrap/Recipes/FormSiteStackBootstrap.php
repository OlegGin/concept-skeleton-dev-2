<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Foundation\TypedConfig;
use Concept\App\View\Twig\TwigAppExtension;
use Concept\Core\Container\ContainerDependency;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Stack\ConceptStack;
use League\Container\Container;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Whoops\Handler\PrettyPageHandler;

/**
 * Site without DB but with forms: Twig + session + CSRF + validation. Content in files.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class FormSiteStackBootstrap
{
    public function boot(Container $container): void
    {
        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);
        $typed = new TypedConfig($config);
        $debug = $config->getBool(ConfigKey::APP_DEBUG);

        $stack = ConceptStack::create();

        $stack->addLogging()
            ->setLevel($config->getString(ConfigKey::LOG_LEVEL, 'ERROR'))
            ->setChannel($config->getString(ConfigKey::LOG_NAME, 'web'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'app.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
            );

        $stack->addCasting()
            ->setCacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::CASTER_CACHE_DIR, 'valinor')))
            ->setDebug($debug);

        $stack->addValidation()
            ->setRules($typed->classMap(ConfigKey::VALIDATOR_RULES, RuleInterface::class))
            ->setGlobalExcept($typed->stringList(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT));

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

        $stack->addHttp()
            ->setRoutes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->setInterceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests();

        $stack->addView()
            ->setPaths($pathManager->rootMap($typed->stringMap(ConfigKey::VIEW_PATHS)))
            ->setExtensions([TwigAppExtension::class])
            ->setRouteNamespaces($typed->stringMap(ConfigKey::VIEW_ROUTE_NAMESPACE))
            ->withTwig()
            ->setViewsPath($pathManager->get(PathName::VIEWS))
            ->setCacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views')))
            ->setDebug($debug);

        $stack->addErrorHandling()
            ->setDebug($debug, new PrettyPageHandler())
            ->setLogReporting()
            ->withViewRenderer($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
