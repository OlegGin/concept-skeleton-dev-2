<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Closure;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
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
use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Container\ContainerInterface;

final class HttpLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @param list<string>|null $routePaths Relative paths under project root (overrides config routes.list when set)
     */
    public function __construct(
        private readonly ?array $routePaths = null,
    ) {}

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
        $transformerClasses = $config->getArray(ConfigKey::CASTER_TRANSFORMERS);
        $container->addServiceProvider(new CastingServiceProvider(
            transformerClasses: $transformerClasses,
            cacheDirectory: $pathManager->get(PathName::CACHE, $config->getString(ConfigKey::CASTER_CACHE_DIR)),
            debug: $config->getBool(ConfigKey::APP_DEBUG),
        ));

        /** @var list<string> $routesList */
        $routesList = $this->routePaths ?? $config->getArray(ConfigKey::ROUTES_LIST);
        /** @var list<class-string<RouteInterceptorInterface>> $interceptors */
        $interceptors = $config->getArray(ConfigKey::ROUTES_INTERCEPTORS);

        $container->addServiceProvider(new HttpKernelServiceProvider(
            routePaths: $pathManager->rootList($routesList),
            resolvers: $this->getArgumentResolvers($container),
            interceptors: $this->getRouteInterceptors($interceptors),
        ));

        $container->addServiceProvider(new HttpServiceProvider());
    }

    /**
     * @return list<ArgumentResolverInterface>
     */
    private function getArgumentResolvers(DefinitionContainerInterface $container): array
    {
        return [
            new FormRequestArgumentResolver(
                formRequestFactory: fn(): FormRequestFactoryInterface => ContainerDependency::get(
                    $container,
                    FormRequestFactoryInterface::class,
                ),
                container: $container,
            ),
            new ServerRequestArgumentResolver(),
            new TypedRouteParameterArgumentResolver(
                fn(): CasterInterface => ContainerDependency::get($container, CasterInterface::class),
            ),
            new RouteParameterArgumentResolver(),
        ];
    }

    /**
     * @param list<class-string<RouteInterceptorInterface>> $interceptors
     * @return list<Closure(ContainerInterface): RouteInterceptorInterface>
     */
    private function getRouteInterceptors(array $interceptors): array
    {
        return array_map(
            static fn(string $interceptorClass): Closure => static function(
                ContainerInterface $container,
            ) use ($interceptorClass): RouteInterceptorInterface {
                return ContainerDependency::get($container, $interceptorClass);
            },
            $interceptors,
        );
    }
}
