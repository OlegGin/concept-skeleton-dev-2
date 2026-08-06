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
 * Internal BFF / proxy API: HTTP + validation + aggressive masking. No DB/session/view —
 * handlers call external services with secrets that must not leak into logs.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class BffStackBootstrap
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
            ->level($config->getString(ConfigKey::LOG_LEVEL, 'INFO'))
            ->channel($config->getString(ConfigKey::LOG_NAME, 'bff'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'bff.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 14),
            )
            ->withMasking();

        $stack->withCasting()
            ->cacheDir($pathManager->get(PathName::CACHE, $config->getString(ConfigKey::CASTER_CACHE_DIR, 'valinor')))
            ->debug($debug);

        $stack->withValidation()
            ->customRules($typed->classMap(ConfigKey::VALIDATOR_RULES, RuleInterface::class))
            ->globalExcept($typed->stringList(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT))
            ->logEnabled($config->getBool(ConfigKey::VALIDATOR_LOG_ENABLED))
            ->logFilePath($pathManager->get(
                PathName::LOGS,
                $config->getString(ConfigKey::VALIDATOR_LOG_FILE, 'validation.log'),
            ))
            ->withMasking();

        $stack->withHttp()
            ->routes($pathManager->rootList($typed->stringList(ConfigKey::ROUTES_LIST)))
            ->interceptors($typed->classList(ConfigKey::ROUTES_INTERCEPTORS, RouteInterceptorInterface::class))
            ->withFormRequests()
            ->withTypedRouteParameters();

        $stack->withErrorHandling()
            ->debug($debug)
            ->reportToLogs()
            ->renderJson();

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
