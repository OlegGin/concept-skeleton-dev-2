<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent;

use Concept\Extensions\DatabaseEloquent\Events\DatabaseQueryExecuted;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\DatabaseEloquent\Commands\DbMigrationListCommand;
use Concept\Extensions\DatabaseEloquent\Contracts\DatabaseInterface;
use Concept\Extensions\DatabaseEloquent\Registries\MigrationRegistry;
use Concept\Extensions\DatabaseEloquent\Registries\SeederRegistry;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Psr\Container\ContainerInterface;

class DatabaseEloquentServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string DEFAULT_TABLE_NAME = 'migrations';

    /**
     * @param array<string, mixed> $connection
     * @param list<string> $migrationPaths
     * @param list<class-string> $seeders
     */
    public function __construct(
        private readonly array $connection,
        private readonly bool $logEnabled,
        private readonly string $logPath,
        private readonly int $logMaxFiles,
        private readonly string $migrationsTable = self::DEFAULT_TABLE_NAME,
        private readonly array $migrationPaths = [],
        private readonly array $seeders = [],
        private readonly bool $emitQueryEvents = false,
    ) {}

    public function provides(string $id): bool
    {
        return in_array($id, [
            CapsuleManager::class,
            DatabaseInterface::class,
            QueryLogger::class,
            Migrator::class,
            SeederManager::class,
            SeederRegistry::class,
            MigrationRegistry::class,
            DbMigrationListCommand::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(DatabaseInterface::class, function() use ($container): Database {
            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);

            return new Database($capsuleManager);
        })->setShared(true);

        $container->add(Migrator::class, function() use ($container): Migrator {
            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);
            $manager = $capsuleManager->getDatabaseManager();
            $migrationTableName = $this->migrationsTable !== '' ? $this->migrationsTable : self::DEFAULT_TABLE_NAME;
            $repository = new DatabaseMigrationRepository($manager, $migrationTableName);

            return new Migrator($repository, $manager, new Filesystem());
        })->setShared(true);

        $container->add(SeederManager::class, function() use ($container): SeederManager {
            /** @var SeederRegistry $seederRegistry */
            $seederRegistry = $container->get(SeederRegistry::class);

            return new SeederManager($container, $seederRegistry);
        })->setShared(true);

        $container->add(SeederRegistry::class, function(): SeederRegistry {
            $seederRegistry = new SeederRegistry();
            $seederRegistry->append($this->seeders);

            return $seederRegistry;
        })->setShared(true);

        $container->add(MigrationRegistry::class, function(): MigrationRegistry {
            $migrationRegistry = new MigrationRegistry();
            $migrationRegistry->append($this->migrationPaths);

            return $migrationRegistry;
        })->setShared(true);

        $container->add(DbMigrationListCommand::class, function() use ($container): DbMigrationListCommand {
            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);
            $migrationTableName = $this->migrationsTable !== '' ? $this->migrationsTable : self::DEFAULT_TABLE_NAME;

            return new DbMigrationListCommand(
                migrationsTable: $migrationTableName,
                capsule: $capsuleManager,
            );
        })->setShared(true);

        $container->add(QueryLogger::class, function() use ($container): QueryLogger {
            $monolog = new Monolog('query');
            $monolog->pushHandler(new RotatingFileHandler(
                $this->logPath,
                $this->logMaxFiles,
                Level::Debug,
            ));

            /** @var DataMaskerInterface|null $masker */
            $masker = $container->has(DataMaskerInterface::class)
                ? $container->get(DataMaskerInterface::class)
                : null;

            return new QueryLogger($monolog, $masker);
        })->setShared(true);
    }

    public function boot(): void
    {
        $container = $this->getContainer();

        $capsuleManager = new CapsuleManager();
        $capsuleManager->addConnection($this->connection);

        $capsuleManager->setAsGlobal();
        $capsuleManager->bootEloquent();
        $capsuleManager->setEventDispatcher(new Dispatcher(new IlluminateContainer()));

        $capsuleManager->getConnection()->listen(function(QueryExecuted $query) use ($container): void {
            $this->logQueries($container, $query);
            $this->dispatchQueryExecuted($container, $query);
        });

        $container->add(CapsuleManager::class, $capsuleManager);
    }

    private function logQueries(ContainerInterface $container, QueryExecuted $query): void
    {
        if (!$this->logEnabled) {
            return;
        }

        if (!$container->has(QueryLogger::class)) {
            return;
        }

        /** @var QueryLogger $queryLogger */
        $queryLogger = $container->get(QueryLogger::class);
        $queryLogger->log($query);
    }

    private function dispatchQueryExecuted(ContainerInterface $container, QueryExecuted $query): void
    {
        if (!$this->emitQueryEvents) {
            return;
        }

        $dispatcher = EventDispatcherResolver::optional($container);
        if ($dispatcher === null) {
            return;
        }

        $dispatcher->dispatch(new DatabaseQueryExecuted(
            sql: $query->sql,
            rawSql: $query->toRawSql(),
            bindings: [...$query->bindings],
            time: (float) $query->time,
            connectionName: $query->connectionName,
        ));
    }
}
