<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Http\Error\AppExceptionReporter;
use Concept\App\Http\Error\TwigHttpErrorRenderer;
use Concept\App\Middleware\RenderHttpErrorMiddleware;
use Concept\Extensions\CastingValinor\CastingServiceProvider;
use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\CastingValinor\Routing\TypedRouteParameterArgumentResolver;
use Concept\Extensions\Config\ConfigServiceProvider;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\ConsoleSymfony\ConsoleSymfonyServiceProvider;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\DatabaseEloquent\DatabaseEloquentServiceProvider;
use Concept\Extensions\DatabaseEloquent\PaginationConfiguratorServiceProvider;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\Extensions\SessionSymfony\SessionServiceProvider;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\ValidationServiceProvider;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\View\Support\ViewRouteNamespaceResolver;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpKernelServiceProvider;
use InvalidArgumentException;
use Closure;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use SessionHandlerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string INCORRECT_SESSION_FILE_PATH = 'Session file path must be a string or null, %s given.';

    private const string CONFIG_DIR = '/config';
    private const string CACHE_VALINOR_DIR = 'valinor';
    private const string CACHE_VIEWS_DIR = 'views';
    private const string ERRORS_FALLBACK_REL = 'resources/views/errors/fallback';
    private const string LOG_APP_FILE = 'app.log';
    private const string LOG_QUERY_FILE = 'query.log';
    private const string LOG_VALIDATION_FILE = 'validation.log';
    private const string LOGS_REL = 'storage/logs';
    private const string CACHE_REL = 'storage/cache';
    private const string VIEWS_REL = 'resources/views';
    private const string STORAGE_REL = 'storage';
    private const string DEFAULT_MIGRATIONS_TABLE = 'migrations';
    private const string DEFAULT_DB_DRIVER = 'mysql';
    private const int DEFAULT_DB_PORT = 3306;
    private const string DEFAULT_DB_CHARSET = 'utf8mb4';
    private const string DEFAULT_DB_COLLATION = 'utf8mb4_unicode_ci';
    private const string DEFAULT_SESSION_PATH = 'sessions';

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

        $container->addServiceProvider(new ConfigServiceProvider(
            root: $this->root,
            configDirectory: $this->root . self::CONFIG_DIR,
        ));

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        $fallbackPath = $this->rootPath(self::ERRORS_FALLBACK_REL);
        $this->registerErrorHandlers($container, $config, $fallbackPath);

        /** @var list<class-string> $transformerClasses */
        $transformerClasses = $config->get(ConfigKey::CASTER_TRANSFORMERS) ?? [];
        $container->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $this->cachePath(self::CACHE_VALINOR_DIR),
            transformerClasses: $transformerClasses,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
        ));

        /** @var array<string, string> $patterns */
        $patterns = $config->get(ConfigKey::MASKING_PATTERNS) ?? [];
        /** @var list<string> $keyPatterns */
        $keyPatterns = $config->get(ConfigKey::MASKING_KEY_PATTERNS) ?? [];
        /** @var list<class-string> $rules */
        $rules = $config->get(ConfigKey::MASKING_RULES) ?? [];
        $container->addServiceProvider(new DataMaskerServiceProvider(
            patterns: $patterns,
            keyPatterns: $keyPatterns,
            rules: $rules,
        ));

        $dataMaskerFactory = $this->dataMaskerFactory($container);

        $container->addServiceProvider(new ValidationServiceProvider(
            customRules: $this->getValidatorRules($config),
            logEnabled: $config->getBool(ConfigKey::VALIDATOR_LOG_ENABLED),
            logPath: $this->logPath($config->getString(ConfigKey::VALIDATOR_LOG_PATH, self::LOG_VALIDATION_FILE)),
            dataMaskerFactory: $dataMaskerFactory,
        ));

        $container->addServiceProvider(new FormRequestServiceProvider(
            globalExcept: $config->getArray(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT),
        ));

        $container->addServiceProvider(new SessionServiceProvider(
            sessionOptions: $this->getSessionOptions($config),
            handler: $this->getSessionHandler($config),
        ));

        $container->addServiceProvider(new CsrfServiceProvider());

        /** @var list<string> $routesList */
        $routesList = $config->get(ConfigKey::ROUTES_LIST) ?? [];
        /** @var list<class-string<RouteInterceptorInterface>> $interceptors */
        $interceptors = $config->get(ConfigKey::ROUTES_INTERCEPTORS) ?? [];

        $container->addServiceProvider(new HttpKernelServiceProvider(
            routePaths: $this->resolvePaths($routesList),
            resolvers: $this->getArgumentResolvers($container),
            interceptors: $interceptors,
            notFoundMiddleware: RenderHttpErrorMiddleware::class,
        ));

        $container->addServiceProvider(new PaginationConfiguratorServiceProvider());

        $container->addServiceProvider(new LoggerMonologServiceProvider(
            path: $this->logPath(self::LOG_APP_FILE),
            level: $config->getString(ConfigKey::LOG_LEVEL),
            maxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            channel: $config->getString(ConfigKey::LOG_NAME),
            dataMaskerFactory: $dataMaskerFactory,
        ));

        /** @var list<string> $migrationPaths */
        $migrationPaths = $config->get(ConfigKey::MIGRATIONS_PATHS) ?? [];
        /** @var list<class-string> $seeders */
        $seeders = $config->get(ConfigKey::SEEDERS_LIST) ?? [];
        $container->addServiceProvider(new DatabaseEloquentServiceProvider(
            connection: $this->getConnectionOptions($config),
            migrationPaths: $this->resolvePaths($migrationPaths),
            migrationsTable: $config->getString(ConfigKey::MIGRATIONS_TABLE, self::DEFAULT_MIGRATIONS_TABLE),
            seeders: $seeders,
            logEnabled: $config->getBool(ConfigKey::DB_LOG_ENABLED),
            logPath: $this->logPath($config->getString(ConfigKey::DB_LOG_PATH, self::LOG_QUERY_FILE)),
            logMaxFiles: $config->getInt(ConfigKey::DB_LOG_MAX_FILES, 7),
            dataMaskerFactory: $dataMaskerFactory,
            emitQueryEvents: $config->getBool(ConfigKey::TELEMETRY_DB_QUERIES),
        ));

        $container->addServiceProvider(new HttpServiceProvider());

        /** @var list<class-string<Command>> $commands */
        $commands = $config->get(ConfigKey::COMMANDS) ?? [];
        $container->addServiceProvider(new ConsoleSymfonyServiceProvider(
            appName: $config->getString(ConfigKey::APP_NAME),
            appVersion: $config->getString(ConfigKey::APP_VERSION),
            commands: $commands,
        ));

        /** @var array<string, string> $viewPaths */
        $viewPaths = $config->get(ConfigKey::VIEW_PATHS) ?? [];
        /** @var array<string, string> $routeNamespace */
        $routeNamespace = $config->get(ConfigKey::VIEW_ROUTE_NAMESPACE) ?? [];
        /** @var list<class-string> $viewExtensions */
        $viewExtensions = $config->get(ConfigKey::VIEW_EXTENSIONS) ?? [];
        $container->addServiceProvider(new ViewServiceProvider(
            paths: $this->resolvePaths($viewPaths),
            extensions: $viewExtensions,
            routeNamespace: $routeNamespace,
        ));

        $container->addServiceProvider(new TwigViewServiceProvider(
            viewsPath: $this->rootPath(self::VIEWS_REL),
            cacheDir: $this->cachePath(self::CACHE_VIEWS_DIR),
            debug: $config->getBool(ConfigKey::APP_DEBUG),
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
     * @return array<string, class-string<RuleInterface>>
     */
    private function getValidatorRules(ConfigInterface $config): array
    {
        /** @var array<string, class-string<RuleInterface>> $rules */
        $rules = $config->get(ConfigKey::VALIDATOR_RULES) ?? [];

        return $rules;
    }

    private function rootPath(string $path): string
    {
        return $this->root . '/' . ltrim($path, '/');
    }

    private function logPath(string $file): string
    {
        return $this->rootPath(self::LOGS_REL . '/' . ltrim($file, '/'));
    }

    private function cachePath(string $dir): string
    {
        return $this->rootPath(self::CACHE_REL . '/' . ltrim($dir, '/'));
    }

    /**
     * @param list<string>|array<string, string> $paths
     * @return list<string>|array<string, string>
     */
    private function resolvePaths(array $paths): array
    {
        return array_map(fn(string $path): string => $this->rootPath($path), $paths);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConnectionOptions(ConfigInterface $config): array
    {
        return [
            'driver' => $config->getString(ConfigKey::DB_DRIVER, self::DEFAULT_DB_DRIVER),
            'host' => $config->getString(ConfigKey::DB_HOST),
            'port' => $config->getInt(ConfigKey::DB_PORT, self::DEFAULT_DB_PORT),
            'database' => $config->getString(ConfigKey::DB_DATABASE),
            'username' => $config->getString(ConfigKey::DB_USERNAME),
            'password' => $config->getString(ConfigKey::DB_PASSWORD),
            'charset' => $config->getString(ConfigKey::DB_CHARSET, self::DEFAULT_DB_CHARSET),
            'collation' => $config->getString(ConfigKey::DB_COLLATION, self::DEFAULT_DB_COLLATION),
            'prefix' => $config->getString(ConfigKey::DB_PREFIX),
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

    private function getSessionHandler(ConfigInterface $config): SessionHandlerInterface
    {
        $sessionFilePath = $config->get(ConfigKey::SESSION_FILE_PATH);
        if (!is_string($sessionFilePath) && !is_null($sessionFilePath)) {
            throw new InvalidArgumentException(sprintf(self::INCORRECT_SESSION_FILE_PATH, get_debug_type($sessionFilePath)));
        }

        if ($sessionFilePath === null || $sessionFilePath === '') {
            $sessionFilePath = self::DEFAULT_SESSION_PATH;
        }

        return new NativeFileSessionHandler(
            $this->rootPath(self::STORAGE_REL . '/' . ltrim($sessionFilePath, '/')),
        );
    }

    /**
     * @return Closure(): ?DataMaskerInterface
     */
    private function dataMaskerFactory(DefinitionContainerInterface $container): Closure
    {
        return fn(): ?DataMaskerInterface => $container->get(DataMaskerInterface::class);
    }

    private function registerErrorHandlers(
        DefinitionContainerInterface $container,
        ConfigInterface $config,
        string $fallbackPath,
    ): void {
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
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            errorsFallbackPath: $fallbackPath,
            exceptionReporter: fn(): ExceptionReporterInterface => $container->get(ExceptionReporterInterface::class),
            httpErrorRenderer: fn(): HttpErrorRendererInterface => $container->get(HttpErrorRendererInterface::class),
        ));
    }
}
