<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\CastingValinor\CastingServiceProvider;
use Concept\Extensions\CastingValinor\Routing\TypedRouteParameterArgumentResolver;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Config\Foundation\PathManager;
use Concept\Extensions\ConsoleSymfony\ConsoleSymfonyServiceProvider;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\DatabaseEloquent\DatabaseEloquentServiceProvider;
use Concept\Extensions\DatabaseEloquent\PaginationConfiguratorServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\Extensions\SessionSymfony\SessionServiceProvider;
use Concept\Extensions\Telemetry\Handlers\TelemetryLogHandler;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\ValidationServiceProvider;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use InvalidArgumentException;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Monolog\Handler\HandlerInterface;
use Psr\Container\ContainerInterface;
use SessionHandlerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string INCORRECT_SESSION_FILE_PATH = 'Session file path must be a string or null, %s given.';

    private const string CACHE_VALINOR_DIR = 'valinor';
    private const string CACHE_VIEWS_DIR = 'views';

    private const string LOG_APP_FILE = 'app.log';
    private const string LOG_QUERY_FILE = 'query.log';
    private const string LOG_VALIDATION_FILE = 'validation.log';

    private const string DEFAULT_MIGRATIONS_TABLE = 'migrations';

    private const string DEFAULT_DB_DRIVER = 'mysql';
    private const string DEFAULT_DB_HOST = '127.0.0.1';
    private const int DEFAULT_DB_PORT = 3306;
    private const string DEFAULT_DB_CHARSET = 'utf8mb4';
    private const string DEFAULT_DB_COLLATION = 'utf8mb4_unicode_ci';

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

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);

        /** @var list<class-string> $transformerClasses */
        $transformerClasses = $config->get(ConfigKey::CASTER_TRANSFORMERS) ?? [];
        $container->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $pathManager->get(PathName::CACHE, self::CACHE_VALINOR_DIR),
            transformerClasses: $transformerClasses,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
        ));

        /** @var array<string, string> $patterns */
        $patterns = $config->get(ConfigKey::MASKING_PATTERNS) ?? [];
        /** @var list<string> $keyPatterns */
        $keyPatterns = $config->get(ConfigKey::MASKING_KEY_PATTERNS) ?? [];
        /** @var list<class-string<DataMaskerRuleInterface>> $rules */
        $rules = $config->get(ConfigKey::MASKING_RULES) ?? [];
        $container->addServiceProvider(new DataMaskerServiceProvider(
            patterns: $patterns,
            keyPatterns: $keyPatterns,
            rules: $rules,
        ));

        $container->addServiceProvider(new LoggerMonologServiceProvider(
            path: $pathManager->get(PathName::LOGS, self::LOG_APP_FILE),
            level: $config->getString(ConfigKey::LOG_LEVEL),
            maxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            channel: $config->getString(ConfigKey::LOG_NAME),
            telemetryHandler: $this->resolveTelemetryHandler($container),
        ));

        /** @var list<string> $migrationPaths */
        $migrationPaths = $config->get(ConfigKey::MIGRATIONS_PATHS) ?? [];
        /** @var list<class-string> $seeders */
        $seeders = $config->get(ConfigKey::SEEDERS_LIST) ?? [];
        $container->addServiceProvider(new DatabaseEloquentServiceProvider(
            connection: $this->getConnectionOptions($config),
            logEnabled: $config->getBool(ConfigKey::DB_LOG_ENABLED),
            logPath: $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::DB_LOG_PATH, self::LOG_QUERY_FILE)),
            logMaxFiles: $config->getInt(ConfigKey::DB_LOG_MAX_FILES, 7),
            migrationsTable: $config->getString(ConfigKey::MIGRATIONS_TABLE, self::DEFAULT_MIGRATIONS_TABLE),
            migrationPaths: $this->relativeToAbsolutePath($pathManager, $migrationPaths),
            seeders: $seeders,
            emitQueryEvents: $config->getBool(ConfigKey::TELEMETRY_DB_QUERIES),
        ));

        /** @var array<string, class-string<RuleInterface>> $validatorRules */
        $validatorRules = $config->get(ConfigKey::VALIDATOR_RULES) ?? [];
        $container->addServiceProvider(new ValidationServiceProvider(
            customRules: $validatorRules,
            logEnabled: $config->getBool(ConfigKey::VALIDATOR_LOG_ENABLED),
            logPath: $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::VALIDATOR_LOG_PATH, self::LOG_VALIDATION_FILE)),
            logMaxFiles: $config->getInt(ConfigKey::VALIDATOR_LOG_MAX_FILES, 7),
        ));

        $container->addServiceProvider(new FormRequestServiceProvider());
        $container->addServiceProvider(new SessionServiceProvider(
            sessionOptions: $this->getSessionOptions($config),
            handler: $this->getSessionHandler($config, $pathManager),
        ));
        $container->addServiceProvider(new CsrfServiceProvider());

        /** @var list<string> $routesList */
        $routesList = $config->get(ConfigKey::ROUTES_LIST) ?? [];
        /** @var list<class-string<RouteInterceptorInterface>> $interceptors */
        $interceptors = $config->get(ConfigKey::ROUTES_INTERCEPTORS) ?? [];
        $container->addServiceProvider(new CoreHttpServiceProvider(
            routePaths: $this->relativeToAbsolutePath($pathManager, $routesList),
            resolvers: $this->getArgumentResolvers($container),
            interceptors: $interceptors,
        ));

        $container->addServiceProvider(new PaginationConfiguratorServiceProvider(
            pageName: $config->getString(ConfigKey::PAGINATION_PAGE_NAME, 'page'),
        ));
        $container->addServiceProvider(new HttpServiceProvider());

        /** @var array<string, string> $viewPaths */
        $viewPaths = $config->get(ConfigKey::VIEW_PATHS) ?? [];
        /** @var array<string, string> $viewContexts */
        $viewContexts = $config->get(ConfigKey::VIEW_CONTEXTS) ?? [];
        /** @var list<class-string> $viewExtensions */
        $viewExtensions = $config->get(ConfigKey::VIEW_EXTENSIONS) ?? [];
        $container->addServiceProvider(new ViewServiceProvider(
            paths: $viewPaths,
            contexts: $viewContexts,
            extensions: $viewExtensions,
        ));

        $container->addServiceProvider(new TwigViewServiceProvider(
            root: $this->root,
            viewsPath: $pathManager->get(PathName::VIEWS),
            cacheDir: $pathManager->get(PathName::CACHE, self::CACHE_VIEWS_DIR),
            debug: $config->getBool(ConfigKey::APP_DEBUG),
        ));

        /** @var list<class-string<Command>> $commands */
        $commands = $config->get(ConfigKey::COMMANDS) ?? [];
        $container->addServiceProvider(new ConsoleSymfonyServiceProvider(
            appName: $config->getString(ConfigKey::APP_NAME),
            appVersion: $config->getString(ConfigKey::APP_VERSION),
            commands: $commands,
        ));

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            root: $this->root,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            errorsFallbackPath: $pathManager->get(PathName::ERRORS_FALLBACK_VIEWS),
        ));
    }

    /**
     * @param PathManager $pathManager
     * @param list<string> $flatList
     * @return list<string>
     */
    private function relativeToAbsolutePath(PathManager $pathManager, array $flatList): array
    {
        return array_map(
            fn(string $path): string => $pathManager->root($path),
            $flatList,
        );
    }

    /**
     * @param ContainerInterface $container
     * @return array<ArgumentResolverInterface>
     */
    private function getArgumentResolvers(ContainerInterface $container): array
    {
        return [
            new FormRequestArgumentResolver($container),
            new ServerRequestArgumentResolver(),
            new TypedRouteParameterArgumentResolver($container),
            new RouteParameterArgumentResolver(),
        ];
    }

    private function resolveTelemetryHandler(ContainerInterface $container): ?HandlerInterface
    {
        if (!$container->has(TelemetryLogHandler::class)) {
            return null;
        }

        /** @var TelemetryLogHandler $handler */
        $handler = $container->get(TelemetryLogHandler::class);

        return $handler;
    }

    /**
     * @return array<string, mixed>
     */
    private function getConnectionOptions(ConfigInterface $config): array
    {
        return [
            'driver' => $config->getString(ConfigKey::DB_DRIVER, self::DEFAULT_DB_DRIVER),
            'host' => $config->getString(ConfigKey::DB_HOST, self::DEFAULT_DB_HOST),
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

    private function getSessionHandler(ConfigInterface $config, PathManager $pathManager): SessionHandlerInterface
    {
        $sessionFilePath = $config->get(ConfigKey::SESSION_FILE_PATH);
        if (!is_string($sessionFilePath) && !is_null($sessionFilePath)) {
            $errorMessage = sprintf(self::INCORRECT_SESSION_FILE_PATH, get_debug_type($sessionFilePath));
            throw new InvalidArgumentException($errorMessage);
        }

        if (empty($sessionFilePath)) {
            return new NativeFileSessionHandler();
        }

        $sessionFilePath = $pathManager->get(PathName::STORAGE, $sessionFilePath);

        return new NativeFileSessionHandler($sessionFilePath);
    }
}
