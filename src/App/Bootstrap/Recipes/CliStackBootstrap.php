<?php declare(strict_types=1);

namespace Concept\App\Bootstrap\Recipes;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Foundation\TypedConfig;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Stack\ConceptStack;
use League\Container\Container;
use Symfony\Component\Console\Command\Command;

/**
 * CLI-only service: console + optional DB. No HTTP kernel, views, session.
 *
 * Sandbox recipe — not wired in bootstrap/app.php.
 */
final class CliStackBootstrap
{
    public function boot(Container $container): void
    {
        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);
        $typed = new TypedConfig($config);

        $stack = ConceptStack::create();

        $stack->addLogging()
            ->setLevel($config->getString(ConfigKey::LOG_LEVEL, 'INFO'))
            ->setChannel($config->getString(ConfigKey::LOG_NAME, 'cli'))
            ->toRotatingFile(
                $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE, 'cli.log')),
                $config->getInt(ConfigKey::LOG_MAX_FILES, 7),
            );

        $stack->addDatabase()
            ->setConnection([
                'driver' => $config->getString(ConfigKey::DB_DRIVER),
                'host' => $config->getString(ConfigKey::DB_HOST),
                'port' => $config->getInt(ConfigKey::DB_PORT, 3306),
                'database' => $config->getString(ConfigKey::DB_DATABASE),
                'username' => $config->getString(ConfigKey::DB_USERNAME),
                'password' => $config->getString(ConfigKey::DB_PASSWORD),
                'charset' => $config->getString(ConfigKey::DB_CHARSET, 'utf8mb4'),
                'collation' => $config->getString(ConfigKey::DB_COLLATION, 'utf8mb4_unicode_ci'),
                'prefix' => $config->getString(ConfigKey::DB_PREFIX),
            ])
            ->setMigrations($pathManager->rootList($typed->stringList(ConfigKey::MIGRATIONS_PATHS)))
            ->setMigrationsTable($config->getString(ConfigKey::MIGRATIONS_TABLE, 'migrations'));

        $stack->addConsole()
            ->setName($config->getString(ConfigKey::APP_NAME, 'CLI'))
            ->setVersion($config->getString(ConfigKey::APP_VERSION, '1.0.0'))
            ->setCommands($typed->classList(ConfigKey::COMMANDS, Command::class));

        // Uncaught errors: EarlyErrorHandlingBootstrap (no HTTP renderer brick).

        foreach ($stack->providers() as $provider) {
            $container->addServiceProvider($provider);
        }
    }
}
