<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\CastingValinor\CastingServiceProvider;
use Concept\Extensions\CastingValinor\Routing\TypedRouteParameterArgumentResolver;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\Extensions\Config\ConfigServiceProvider;
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
use Concept\Extensions\ValidationRakit\ValidationServiceProvider;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string BOOTSTRAP_PATHS_FILE = 'bootstrap/paths.php';
    private const string DEFAULT_CONFIG_DIR = 'config';

    private const string CACHE_VALINOR_DIR = 'valinor';
    private const string CACHE_VIEWS_DIR = 'views';

    private const string LOG_APP_FILE = 'app.log';
    private const string LOG_QUERY_FILE = 'query.log';

    private const string STORAGE_SESSIONS_DIR = 'sessions';

    private const string DEFAULT_MIGRATIONS_TABLE = 'migrations';

    private const string DEFAULT_DB_DRIVER = 'mysql';
    private const string DEFAULT_DB_HOST = '127.0.0.1';
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

        /** @var array<string, string> $paths */
        $paths = require $this->root . '/' . self::BOOTSTRAP_PATHS_FILE;
        $container->addServiceProvider(new ConfigServiceProvider(
            root: $this->root,
            configDir: $paths[PathName::CONFIG] ?? self::DEFAULT_CONFIG_DIR,
            pathMap: $paths,
        ));

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
        ));

        /** @var list<string> $migrationPaths */
        $migrationPaths = $config->get(ConfigKey::MIGRATIONS_PATHS) ?? [];
        /** @var list<class-string> $seeders */
        $seeders = $config->get(ConfigKey::SEEDERS_LIST) ?? [];
        $container->addServiceProvider(new DatabaseEloquentServiceProvider(
            connection: $this->getConnectionOptions($config),
            logDbQueries: $config->getBool(ConfigKey::LOG_DB_QUERIES),
            queryLogPath: $pathManager->get(PathName::LOGS, self::LOG_QUERY_FILE),
            logMaxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            migrationsTable: $config->getString(ConfigKey::MIGRATIONS_TABLE, self::DEFAULT_MIGRATIONS_TABLE),
            migrationPaths: $this->relativeToAbsolutePath($pathManager, $migrationPaths),
            seeders: $seeders,
        ));

        $container->addServiceProvider(new ValidationServiceProvider());
        $container->addServiceProvider(new FormRequestServiceProvider());
        $container->addServiceProvider(new SessionServiceProvider(
            savePath: $pathManager->get(PathName::STORAGE, self::STORAGE_SESSIONS_DIR),
        ));
        $container->addServiceProvider(new CsrfServiceProvider());
        /** @var list<string> $routesList */
        $routesList = $config->get(ConfigKey::ROUTES_LIST) ?? [];

        $container->addServiceProvider(new CoreHttpServiceProvider(
            routePaths: $this->relativeToAbsolutePath($pathManager, $routesList),
            resolvers: $this->getArgumentResolvers($container),
        ));

        $container->addServiceProvider(new PaginationConfiguratorServiceProvider());
        $container->addServiceProvider(new HttpServiceProvider());

        /** @var array<string, string> $viewPaths */
        $viewPaths = $config->get(ConfigKey::VIEW_PATHS) ?? [];
        /** @var list<class-string> $viewExtensions */
        $viewExtensions = $config->get(ConfigKey::VIEW_EXTENSIONS) ?? [];
        $container->addServiceProvider(new ViewServiceProvider(
            paths: $viewPaths,
            extensions: $viewExtensions,
        ));

        $container->addServiceProvider(new TwigViewServiceProvider(
            root: $this->root,
            viewsPath: $pathManager->get(PathName::VIEWS),
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            cacheDir: $pathManager->get(PathName::CACHE, self::CACHE_VIEWS_DIR),
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

    /**
     * @param ConfigInterface $config
     * @return array<string, string>
     */
    private function getConnectionOptions(ConfigInterface $config): array
    {
        return [
            'driver' => $config->getString(ConfigKey::DB_DRIVER, self::DEFAULT_DB_DRIVER),
            'host' => $config->getString(ConfigKey::DB_HOST, self::DEFAULT_DB_HOST),
            'database' => $config->getString(ConfigKey::DB_DATABASE),
            'username' => $config->getString(ConfigKey::DB_USERNAME),
            'password' => $config->getString(ConfigKey::DB_PASSWORD),
            'charset' => $config->getString(ConfigKey::DB_CHARSET, self::DEFAULT_DB_CHARSET),
            'collation' => $config->getString(ConfigKey::DB_COLLATION, self::DEFAULT_DB_COLLATION),
            'prefix' => $config->getString(ConfigKey::DB_PREFIX),
        ];
    }
}
