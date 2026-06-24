<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\Middleware\HandleCsrfExceptionMiddleware;
use Concept\App\Middleware\HandleValidationExceptionMiddleware;
use Concept\App\Middleware\ShareViewDataMiddleware;
use Concept\App\View\Twig\AppExtension;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\Casting\CastingServiceProvider;
use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\Casting\Routing\TypedRouteParameterArgumentResolver;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\Session\Contracts\FlashBagInterface;
use Concept\Extensions\Session\SessionServiceProvider;
use Concept\Extensions\Validation\ValidationServiceProvider;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
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
        $container = $this->getContainer();
        $this->registerCastingProvider();
        $container->addServiceProvider(new ValidationServiceProvider());
        $container->addServiceProvider(new FormRequestServiceProvider());
        $this->registerSessionProvider();
        $container->addServiceProvider(new CsrfServiceProvider());
        $this->registerRoutingProvider();
        $container->addServiceProvider(new HttpServiceProvider());
        $this->registerMiddlewareBindings();
        $this->registerViewProvider();
        $this->registerTwigViewProvider();
    }

    public function register(): void
    {
    }

    private function registerCastingProvider(): void
    {
        $this->getContainer()->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $this->root . '/storage/cache/valinor',
            debug: $this->appDebug(),
        ));
    }

    private function registerSessionProvider(): void
    {
        $this->getContainer()->addServiceProvider(new SessionServiceProvider(
            savePath: $this->root . '/storage/sessions',
        ));
    }

    private function registerRoutingProvider(): void
    {
        $container = $this->getContainer();
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

    private function registerMiddlewareBindings(): void
    {
        $container = $this->getContainer();

        $container->add(HandleValidationExceptionMiddleware::class, function () use ($container): HandleValidationExceptionMiddleware {
            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $container->get(ResponseFactoryInterface::class);
            /** @var RequestFormat $requestFormat */
            $requestFormat = $container->get(RequestFormat::class);
            /** @var FlashBagInterface $flashBag */
            $flashBag = $container->get(FlashBagInterface::class);

            return new HandleValidationExceptionMiddleware($responseFactory, $requestFormat, $flashBag);
        })->setShared(true);

        $container->add(HandleCsrfExceptionMiddleware::class, function () use ($container): HandleCsrfExceptionMiddleware {
            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $container->get(ResponseFactoryInterface::class);
            /** @var RequestFormat $requestFormat */
            $requestFormat = $container->get(RequestFormat::class);
            /** @var FlashBagInterface $flashBag */
            $flashBag = $container->get(FlashBagInterface::class);

            return new HandleCsrfExceptionMiddleware($responseFactory, $requestFormat, $flashBag);
        })->setShared(true);

        $container->add(ShareViewDataMiddleware::class, function () use ($container): ShareViewDataMiddleware {
            /** @var FlashBagInterface $flashBag */
            $flashBag = $container->get(FlashBagInterface::class);
            /** @var CsrfTokenManagerInterface $csrfTokenManager */
            $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

            return new ShareViewDataMiddleware($flashBag, $csrfTokenManager);
        })->setShared(true);
    }

    private function registerViewProvider(): void
    {
        $container = $this->getContainer();
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
        $this->getContainer()->addServiceProvider(new TwigViewServiceProvider(
            root: $this->root,
            viewsPath: $this->root . '/resources/views',
            debug: $this->appDebug(),
            cacheDir: $this->root . '/storage/cache/views',
        ));
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
}
