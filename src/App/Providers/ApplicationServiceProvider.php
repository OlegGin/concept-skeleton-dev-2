<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\View\Twig\AppExtension;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\Casting\CastingServiceProvider;
use Concept\Extensions\Casting\Routing\TypedRouteParameterArgumentResolver;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\Validation\ValidationServiceProvider;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function __construct(private readonly string $root) {}

    public function provides(string $id): bool
    {
        return false;
    }

    public function boot(): void
    {
        $this->registerCastingProvider();
        $this->registerValidationProvider();
        $this->registerFormRequestProvider();
        $this->registerRoutingProvider();
        $this->container()->addServiceProvider(new HttpServiceProvider());
        $this->registerViewProvider();
        $this->registerTwigViewProvider();
    }

    public function register(): void
    {
    }

    private function registerCastingProvider(): void
    {
        $this->container()->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $this->root . '/storage/cache/valinor',
            debug: $this->appDebug(),
        ));
    }

    private function registerValidationProvider(): void
    {
        $this->container()->addServiceProvider(new ValidationServiceProvider());
    }

    private function registerFormRequestProvider(): void
    {
        $this->container()->addServiceProvider(new FormRequestServiceProvider());
    }

    private function registerRoutingProvider(): void
    {
        $container = $this->container();

        $container->addServiceProvider(new CoreHttpServiceProvider(
            routePaths: [$this->root . '/routes/web.php'],
            resolvers: [
                new FormRequestArgumentResolver($container),
                new ServerRequestArgumentResolver(),
                new TypedRouteParameterArgumentResolver($container),
                new RouteParameterArgumentResolver(),
            ],
        ));
    }

    private function container(): DefinitionContainerInterface
    {
        return $this->getContainer();
    }

    private function appDebug(): bool
    {
        return filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
    }

    private function appName(): string
    {
        $name = $_ENV['APP_NAME'] ?? 'Concept';

        return is_string($name) ? $name : 'Concept';
    }

    private function registerViewProvider(): void
    {
        $container = $this->container();

        $container->add(AppExtension::class, fn (): AppExtension => new AppExtension(
            $this->appName(),
        ))->setShared(true);

        $container->addServiceProvider(new ViewServiceProvider(
            paths: [
                'app' => '/resources/views',
            ],
            extensions: [
                AppExtension::class,
            ],
        ));
    }

    private function registerTwigViewProvider(): void
    {
        $this->container()->addServiceProvider(new TwigViewServiceProvider(
            root: $this->root,
            viewsPath: $this->root . '/resources/views',
            debug: $this->appDebug(),
            cacheDir: $this->root . '/storage/cache/views',
        ));
    }
}
