<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\Http\Error\AppExceptionReporter;
use Concept\App\Http\Error\TwigHttpErrorRenderer;
use Concept\App\Middleware\RenderHttpErrorMiddleware;
use Concept\App\View\Twig\TwigAppExtension;
use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpKernelServiceProvider;
use Concept\Extensions\CastingValinor\CastingServiceProvider;
use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\CastingValinor\Routing\TypedRouteParameterArgumentResolver;
use Concept\Extensions\ConsoleSymfony\ConsoleSymfonyServiceProvider;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Console\Commands\RouteListCommand;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\Extensions\SessionSymfony\SessionServiceProvider;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\View\Support\ViewRouteNamespaceResolver;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use Concept\Extensions\ValidationRakit\ValidationServiceProvider;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use SessionHandlerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const bool DEBUG = true;

    private const string APP_NAME = 'Concept Skeleton';
    private const string APP_VERSION = '1.0.0';

    private const string ROUTES_WEB = '/routes/web.php';
    private const string ROUTES_API = '/routes/api.php';
    private const string VIEWS_FRONTEND = '/resources/views/frontend';
    private const string VIEWS_ROOT = '/resources/views';
    private const string CACHE_VALINOR = '/storage/cache/valinor';
    private const string CACHE_VIEWS = '/storage/cache/views';
    private const string ERRORS_FALLBACK = '/resources/views/errors/fallback';
    private const string LOG_APP_FILE = '/storage/logs/app.log';
    private const string LOG_VALIDATION_FILE = '/storage/logs/validation.log';
    private const string SESSION_SAVE_PATH = '/storage/sessions';
    private const string LOG_LEVEL = 'debug';
    private const int LOG_MAX_FILES = 7;
    private const string LOG_CHANNEL = 'app';

    /**
     * @param string $root
     */
    public function __construct(private readonly string $root) {}

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

        $fallbackPath = $this->root . self::ERRORS_FALLBACK;
        $this->registerErrorHandlers($container, $fallbackPath);

        $container->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $this->root . self::CACHE_VALINOR,
            transformerClasses: [],
            debug: self::DEBUG,
        ));

        $container->addServiceProvider(new DataMaskerServiceProvider(
            patterns: $this->getDataMaskerPatterns(),
            keyPatterns: $this->getDataMaskerKeyPatterns(),
            rules: [],
        ));

        $dataMaskerFactory = $this->dataMaskerFactory($container);

        $container->addServiceProvider(new ValidationServiceProvider(
            customRules: [],
            logEnabled: self::DEBUG,
            logPath: $this->root . self::LOG_VALIDATION_FILE,
            dataMaskerFactory: $dataMaskerFactory,
        ));

        $container->addServiceProvider(new FormRequestServiceProvider(
            globalExcept: ['_csrf_token'],
        ));

        $container->addServiceProvider(new SessionServiceProvider(
            sessionOptions: $this->getSessionOptions(),
            handler: $this->getSessionHandler(),
        ));

        $container->addServiceProvider(new CsrfServiceProvider());

        $container->addServiceProvider(new HttpKernelServiceProvider(
            routePaths: [
                $this->root . self::ROUTES_WEB,
                $this->root . self::ROUTES_API,
            ],
            resolvers: $this->getArgumentResolvers($container),
            notFoundMiddleware: RenderHttpErrorMiddleware::class,
        ));

        $container->addServiceProvider(new LoggerMonologServiceProvider(
            path: $this->root . self::LOG_APP_FILE,
            level: self::LOG_LEVEL,
            maxFiles: self::LOG_MAX_FILES,
            channel: self::LOG_CHANNEL,
            dataMaskerFactory: $dataMaskerFactory,
        ));

        $container->addServiceProvider(new HttpServiceProvider());

        $container->addServiceProvider(new ConsoleSymfonyServiceProvider(
            appName: self::APP_NAME,
            appVersion: self::APP_VERSION,
            commands: $this->getConsoleCommands(),
        ));

        $container->addServiceProvider(new ViewServiceProvider(
            paths: [
                'frontend' => $this->root . self::VIEWS_FRONTEND,
            ],
            extensions: [
                TwigAppExtension::class,
            ],
        ));

        $container->addServiceProvider(new TwigViewServiceProvider(
            viewsPath: $this->root . self::VIEWS_ROOT,
            cacheDir: $this->root . self::CACHE_VIEWS,
            debug: self::DEBUG,
        ));
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
                fn(): CasterInterface => $container->get(CasterInterface::class),
            ),
            new RouteParameterArgumentResolver(),
        ];
    }

    /**
     * @return list<class-string<Command>>
     */
    private function getConsoleCommands(): array
    {
        return [
            RouteListCommand::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSessionOptions(): array
    {
        return [
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'cookie_domain' => '',
            'cookie_samesite' => 'Lax',
            'use_only_cookies' => true,
            'use_strict_mode' => true,
        ];
    }

    private function getSessionHandler(): SessionHandlerInterface
    {
        return new NativeFileSessionHandler($this->root . self::SESSION_SAVE_PATH);
    }

    /**
     * @return Closure(): ?DataMaskerInterface
     */
    private function dataMaskerFactory(DefinitionContainerInterface $container): Closure
    {
        return fn(): ?DataMaskerInterface => $container->get(DataMaskerInterface::class);
    }

    /**
     * @return array<string, string>
     */
    private function getDataMaskerPatterns(): array
    {
        return [
            '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.[a-z]{2,}/i' => '***@***.***',
            '/\d{4}-\d{4}-\d{4}-\d{4}/' => '****-****-****-****',
            '/(password|passwd|pwd|repeat_password|password_confirmation|token|_csrf_token|csrf_token|api_key|secret|authorization)[:=]+([^\s,;]+)/i' => '$1=*****',
        ];
    }

    /**
     * @return list<string>
     */
    private function getDataMaskerKeyPatterns(): array
    {
        return [
            '/.*password.*/i',
            '/.*token.*/i',
            '/.*_csrf_token.*/i',
            '/.*secret.*/i',
            '/api_key/i',
            '/authorization/i',
        ];
    }

    private function registerErrorHandlers(DefinitionContainerInterface $container, string $fallbackPath): void
    {
        $container->add(ExceptionReporterInterface::class, function() use ($container): AppExceptionReporter {
            return new AppExceptionReporter(
                logger: $container->get(LoggerInterface::class),
                request: $container->get(ServerRequestInterface::class),
            );
        })->setShared(true);

        $container->add(TwigHttpErrorRenderer::class, function() use ($container, $fallbackPath): TwigHttpErrorRenderer {
            return new TwigHttpErrorRenderer(
                responseFactory: $container->get(ResponseFactoryInterface::class),
                viewResponse: $container->get(ViewResponseFactoryInterface::class),
                requestFormat: $container->get(RequestFormat::class),
                routeNamespaceResolver: $container->get(ViewRouteNamespaceResolver::class),
                exceptionReporter: $container->get(ExceptionReporterInterface::class),
                fallbackPath: $fallbackPath,
            );
        });

        $container->add(HttpErrorRendererInterface::class, fn(): TwigHttpErrorRenderer => $container->get(TwigHttpErrorRenderer::class))
            ->setShared(true);

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            debug: self::DEBUG,
            errorsFallbackPath: $fallbackPath,
            exceptionReporter: fn(): ExceptionReporterInterface => $container->get(ExceptionReporterInterface::class),
            httpErrorRenderer: fn(): HttpErrorRendererInterface => $container->get(HttpErrorRendererInterface::class),
        ));
    }
}
