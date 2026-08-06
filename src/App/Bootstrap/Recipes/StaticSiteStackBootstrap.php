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
use Concept\Stack\ConceptStack;
use League\Container\Container;
use Whoops\Handler\PrettyPageHandler;

/**
 * Marketing / brochure site: routes + Twig. No DB, session, forms, validation.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class StaticSiteStackBootstrap
{
    public function boot(Container $container): void
    {
        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);
        $typed = new TypedConfig($config);
        $debug = $config->getBool(ConfigKey::APP_DEBUG);

        $stack = ConceptStack::create();

        $stack->withLogging()
            ->level($config->getString(ConfigKey::LOG_LEVEL, 'ERROR'))
            ->channel($config->getString(ConfigKey::LOG_NAME, 'web'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'app.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
            );

        $stack->withHttp()
            ->routes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->interceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class));

        $stack->withView()
            ->paths($pathManager->rootMap($typed->stringMap(ConfigKey::VIEW_PATHS)))
            ->extensions([TwigAppExtension::class])
            ->routeNamespace($typed->stringMap(ConfigKey::VIEW_ROUTE_NAMESPACE))
            ->withTwig()
            ->viewsPath($pathManager->get(PathName::VIEWS))
            ->cacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views')))
            ->debug($debug);

        $stack->withErrorHandling()
            ->debug($debug, new PrettyPageHandler())
            ->reportToLogs()
            ->renderView($pathManager->get(PathName::ERRORS_FALLBACK_VIEWS));

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
