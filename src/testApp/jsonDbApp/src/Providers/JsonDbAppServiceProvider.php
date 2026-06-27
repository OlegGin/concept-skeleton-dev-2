<?php declare(strict_types=1);

namespace Concept\testApp\jsonDbApp\src\Providers;

use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\Config\ConfigServiceProvider;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\DatabaseEloquent\DatabaseEloquentServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\testApp\jsonDbApp\src\Foundation\ConfigKey;
use Concept\testApp\jsonDbApp\src\Foundation\PathName;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class JsonDbAppServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string DEFAULT_TIMEZONE = 'UTC';
    private const string DEFAULT_DB_DRIVER = 'mysql';
    private const string DEFAULT_DB_HOST = '127.0.0.1';
    private const int DEFAULT_DB_PORT = 3306;
    private const string DEFAULT_DB_CHARSET = 'utf8mb4';
    private const string DEFAULT_DB_COLLATION = 'utf8mb4_unicode_ci';
    private const string DEFAULT_MIGRATIONS_TABLE = 'jsondb_migrations';
    private const string LOG_FILE = 'app.log';
    private const string LOG_QUERY_FILE = 'query.log';

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
            configDir: PathName::CONFIG,
        ));

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        date_default_timezone_set($config->getString(ConfigKey::APP_TIMEZONE, self::DEFAULT_TIMEZONE));

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
            path: $this->rootPath(PathName::LOGS, self::LOG_FILE),
            level: $config->getString(ConfigKey::LOG_LEVEL),
            maxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            channel: $config->getString(ConfigKey::LOG_NAME),
        ));

        /** @var list<string> $migrationPaths */
        $migrationPaths = $config->get(ConfigKey::MIGRATIONS_PATHS) ?? [];
        $container->addServiceProvider(new DatabaseEloquentServiceProvider(
            connection: $this->getConnectionOptions($config),
            logEnabled: $config->getBool(ConfigKey::DB_LOG_ENABLED),
            logPath: $this->rootPath(PathName::LOGS, $config->getString(ConfigKey::DB_LOG_PATH, self::LOG_QUERY_FILE)),
            logMaxFiles: $config->getInt(ConfigKey::DB_LOG_MAX_FILES, 7),
            migrationsTable: $config->getString(ConfigKey::MIGRATIONS_TABLE, self::DEFAULT_MIGRATIONS_TABLE),
            migrationPaths: $this->toAbsolutePaths($migrationPaths),
        ));

        /** @var list<string> $routesList */
        $routesList = $config->get(ConfigKey::ROUTES_LIST) ?? [];
        $container->addServiceProvider(new CoreHttpServiceProvider(
            routePaths: $this->toAbsolutePaths($routesList),
            resolvers: [
                new ServerRequestArgumentResolver(),
                new RouteParameterArgumentResolver(),
            ],
            interceptors: [],
        ));

        $container->addServiceProvider(new HttpServiceProvider());

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            root: $this->root,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            errorsFallbackPath: $this->root,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function getConnectionOptions(ConfigInterface $config): array
    {
        $driver = $config->getString(ConfigKey::DB_DRIVER, self::DEFAULT_DB_DRIVER);
        $database = $config->getString(ConfigKey::DB_DATABASE);

        if ($driver === 'sqlite') {
            $database = $this->rootPath($database);
            $this->ensureSqliteDatabase($database);
        }

        $options = [
            'driver' => $driver,
            'database' => $database,
            'prefix' => $config->getString(ConfigKey::DB_PREFIX),
        ];

        if ($driver !== 'sqlite') {
            $options['host'] = $config->getString(ConfigKey::DB_HOST, self::DEFAULT_DB_HOST);
            $options['port'] = $config->getInt(ConfigKey::DB_PORT, self::DEFAULT_DB_PORT);
            $options['username'] = $config->getString(ConfigKey::DB_USERNAME);
            $options['password'] = $config->getString(ConfigKey::DB_PASSWORD);
            $options['charset'] = $config->getString(ConfigKey::DB_CHARSET, self::DEFAULT_DB_CHARSET);
            $options['collation'] = $config->getString(ConfigKey::DB_COLLATION, self::DEFAULT_DB_COLLATION);
        }

        return $options;
    }

    private function ensureSqliteDatabase(string $databasePath): void
    {
        $this->ensureDirectory(dirname($databasePath));

        if (is_file($databasePath)) {
            return;
        }

        touch($databasePath);
    }

    private function ensureDirectory(string $directory): void
    {
        if ($directory === '' || is_dir($directory)) {
            return;
        }

        mkdir($directory, 0775, true);
    }

    private function rootPath(string $path = '', string $subPath = ''): string
    {
        $fullPath = $this->root . '/' . ltrim($path, '/');

        return $subPath !== '' ? $fullPath . '/' . ltrim($subPath, '/') : $fullPath;
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function toAbsolutePaths(array $paths): array
    {
        return array_map(
            fn(string $path): string => $this->rootPath($path),
            $paths,
        );
    }
}
