<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Middleware\RenderHttpErrorMiddleware;
use Concept\Core\Container\ContainerDependency;
use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpKernelServiceProvider;
use Concept\Extensions\CastingValinor\CastingServiceProvider;
use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\CastingValinor\Routing\TypedRouteParameterArgumentResolver;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\SessionSymfony\SessionServiceProvider;
use InvalidArgumentException;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

final class HttpLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string INCORRECT_SESSION_FILE_PATH = 'Session file path must be a string or null, %s given.';

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

        /** @var list<class-string> $transformerClasses */
        $transformerClasses = $config->get(ConfigKey::CASTER_TRANSFORMERS) ?? [];
        $container->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $pathManager->get(PathName::CACHE, $config->getString(ConfigKey::CASTER_CACHE_DIR)),
            transformerClasses: $transformerClasses,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
        ));

        $container->addServiceProvider(new SessionServiceProvider(
            sessionOptions: $this->getSessionOptions($config),
            handler: $this->getSessionHandler($config, $pathManager),
        ));

        $container->addServiceProvider(new CsrfServiceProvider());

        /** @var list<string> $routesList */
        $routesList = $config->get(ConfigKey::ROUTES_LIST) ?? [];
        /** @var list<class-string<RouteInterceptorInterface>> $interceptors */
        $interceptors = $config->get(ConfigKey::ROUTES_INTERCEPTORS) ?? [];

        $container->addServiceProvider(new HttpKernelServiceProvider(
            routePaths: $pathManager->rootList($routesList),
            resolvers: $this->getArgumentResolvers($container),
            interceptors: $interceptors,
            notFoundMiddleware: RenderHttpErrorMiddleware::class,
        ));

        $container->addServiceProvider(new HttpServiceProvider());
    }

    /**
     * @return list<ArgumentResolverInterface>
     */
    private function getArgumentResolvers(DefinitionContainerInterface $container): array
    {
        return [
            new FormRequestArgumentResolver($container),
            new ServerRequestArgumentResolver(),
            new TypedRouteParameterArgumentResolver(
                fn(): CasterInterface => ContainerDependency::get($container, CasterInterface::class),
            ),
            new RouteParameterArgumentResolver(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSessionOptions(ConfigInterface $config): array
    {
        return [
            'cookie_lifetime' => $config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME, 0),
            'cookie_path' => $config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
            'cookie_secure' => $config->getBool(ConfigKey::SESSION_COOKIE_SECURE, false),
            'cookie_httponly' => $config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
            'cookie_domain' => $config->getString(ConfigKey::SESSION_COOKIE_DOMAIN, ''),
            'cookie_samesite' => $config->getString(ConfigKey::SESSION_COOKIE_SAMESITE, 'Lax'),
            'use_only_cookies' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_ONLY_COOKIES, true),
            'use_strict_mode' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_STRICT_MODE, true),
        ];
    }

    private function getSessionHandler(ConfigInterface $config, PathManager $pathManager): SessionHandlerInterface
    {
        $sessionFilePath = $config->get(ConfigKey::SESSION_FILE_PATH);
        if (!is_string($sessionFilePath) && !is_null($sessionFilePath)) {
            throw new InvalidArgumentException(sprintf(self::INCORRECT_SESSION_FILE_PATH, get_debug_type($sessionFilePath)));
        }

        if ($sessionFilePath === null || $sessionFilePath === '') {
            return new NativeFileSessionHandler();
        }

        return new NativeFileSessionHandler(
            $pathManager->get(PathName::STORAGE, $sessionFilePath),
        );
    }
}
