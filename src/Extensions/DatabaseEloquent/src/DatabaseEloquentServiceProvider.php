<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent;

use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\DatabaseEloquent\Contracts\DatabaseInterface;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Psr\Container\ContainerInterface;

class DatabaseEloquentServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @param array<string, string> $connection
     */
    public function __construct(
        private readonly array $connection,
        private readonly bool $logDbQueries,
        private readonly string $queryLogPath,
        private readonly int $logMaxFiles,
    ) {}

    public function provides(string $id): bool
    {
        return in_array($id, [
            CapsuleManager::class,
            DatabaseInterface::class,
            QueryLogger::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(DatabaseInterface::class, function () use ($container): Database {
            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);

            return new Database($capsuleManager);
        })->setShared(true);

        $container->add(QueryLogger::class, function () use ($container): QueryLogger {
            $monolog = new Monolog('query');
            $monolog->pushHandler(new RotatingFileHandler(
                $this->queryLogPath,
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

        $capsuleManager->getConnection()->listen(function (QueryExecuted $query) use ($container): void {
            $this->logQueries($container, $query);
        });

        $container->add(CapsuleManager::class, $capsuleManager);
    }

    private function logQueries(ContainerInterface $container, QueryExecuted $query): void
    {
        if (!$this->logDbQueries) {
            return;
        }

        if (!$container->has(QueryLogger::class)) {
            return;
        }

        /** @var QueryLogger $queryLogger */
        $queryLogger = $container->get(QueryLogger::class);
        $queryLogger->log($query);
    }
}
