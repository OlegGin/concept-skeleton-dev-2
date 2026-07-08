<?php declare(strict_types=1);

namespace Concept\Components\Health\Providers;

use Concept\Components\Health\Checks\ComponentsPresentCheck;
use Concept\Components\Health\Checks\DatabasePingCheck;
use Concept\Components\Health\Commands\AppHealthCommand;
use Concept\Components\Health\Contracts\HealthCheckInterface;
use Concept\Extensions\Components\ComponentRegistry;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class HealthServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            AppHealthCommand::class,
            DatabasePingCheck::class,
            ComponentsPresentCheck::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(DatabasePingCheck::class, function() use ($container): DatabasePingCheck {
            return new DatabasePingCheck($container);
        })->setShared(true);

        $container->add(ComponentsPresentCheck::class, function() use ($container): ComponentsPresentCheck {
            /** @var ComponentRegistry $registry */
            $registry = $container->get(ComponentRegistry::class);

            return new ComponentsPresentCheck($registry);
        })->setShared(true);

        $container->add(AppHealthCommand::class, function() use ($container): AppHealthCommand {
            /** @var list<HealthCheckInterface> $checks */
            $checks = [
                $container->get(ComponentsPresentCheck::class),
                $container->get(DatabasePingCheck::class),
            ];

            return new AppHealthCommand($checks);
        })->setShared(true);
    }
}
