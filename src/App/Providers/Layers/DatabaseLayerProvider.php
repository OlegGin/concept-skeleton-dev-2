<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Providers\Support\ApplicationPaths;
use Concept\App\Providers\Support\DataMaskerFactory;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DatabaseEloquent\DatabaseEloquentServiceProvider;
use Concept\Extensions\DatabaseEloquent\PaginationConfiguratorServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class DatabaseLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string DEFAULT_MIGRATIONS_TABLE = 'migrations';
    private const string DEFAULT_DB_DRIVER = 'mysql';
    private const int DEFAULT_DB_PORT = 3306;
    private const string DEFAULT_DB_CHARSET = 'utf8mb4';
    private const string DEFAULT_DB_COLLATION = 'utf8mb4_unicode_ci';

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
        $paths = new ApplicationPaths($pathManager);

        $container->addServiceProvider(new PaginationConfiguratorServiceProvider());

        /** @var list<string> $migrationPaths */
        $migrationPaths = $config->get(ConfigKey::MIGRATIONS_PATHS) ?? [];
        /** @var list<class-string> $seeders */
        $seeders = $config->get(ConfigKey::SEEDERS_LIST) ?? [];
        $container->addServiceProvider(new DatabaseEloquentServiceProvider(
            connection: $this->getConnectionOptions($config),
            migrationPaths: $paths->resolveList($migrationPaths),
            migrationsTable: $config->getString(ConfigKey::MIGRATIONS_TABLE, self::DEFAULT_MIGRATIONS_TABLE),
            seeders: $seeders,
            logEnabled: $config->getBool(ConfigKey::DB_LOG_ENABLED),
            logFilePath: $paths->logFile($config->getString(ConfigKey::DB_LOG_FILE)),
            logMaxFiles: $config->getInt(ConfigKey::DB_LOG_MAX_FILES, 7),
            dataMaskerFactory: DataMaskerFactory::fromContainer($container),
            emitQueryEvents: $config->getBool(ConfigKey::TELEMETRY_DB_QUERIES),
        ));
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
}
