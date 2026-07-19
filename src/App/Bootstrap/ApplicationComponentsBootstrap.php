<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Foundation\ConfigKey;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Components\Commands\ComponentListCommand;
use Concept\Extensions\Components\Commands\ComponentPublishAssetsCommand;
use Concept\Extensions\Components\ComponentRegistry;
use Concept\Extensions\Components\ComponentsServiceProvider;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DatabaseEloquent\Registries\MigrationRegistry;
use Concept\Extensions\DatabaseEloquent\Registries\SeederRegistry;
use Concept\Extensions\View\Registry\ViewRegistry;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Route\Router;
use Symfony\Component\Console\Application as ConsoleApplication;

final class ApplicationComponentsBootstrap extends AbstractServiceProvider implements BootableServiceProviderInterface
{
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

        $config = ContainerDependency::get($container, ConfigInterface::class);

        /** @var list<class-string<ComponentInterface>> $componentClasses */
        $componentClasses = $config->getArray(ConfigKey::COMPONENTS);

        $container->addServiceProvider(new ComponentsServiceProvider(
            componentClasses: $componentClasses,
            seedersRegistrar: function(ComponentRegistry $registry) use ($container): void {
                ContainerDependency::get($container, SeederRegistry::class)->append($registry->seeders());
            },
            migrationsRegistrar: function(ComponentRegistry $registry) use ($container): void {
                ContainerDependency::get($container, MigrationRegistry::class)->append($registry->migrationPaths());
            },
            commandsRegistrar: function(ComponentRegistry $registry) use ($container): void {
                $consoleApplication = ContainerDependency::get($container, ConsoleApplication::class);

                $consoleApplication->addCommand(new ComponentListCommand($registry));
                $consoleApplication->addCommand(new ComponentPublishAssetsCommand($registry));

                foreach ($registry->commands() as $commandClass) {
                    $command = ContainerDependency::get($container, $commandClass);
                    $consoleApplication->addCommand($command);
                }
            },
            routesRegistrar: function(ComponentRegistry $registry) use ($container): void {
                $router = ContainerDependency::get($container, Router::class);

                foreach ($registry->routes() as $routesFile) {
                    require $routesFile;
                }
            },
            viewFeaturesRegistrar: PHP_SAPI !== 'cli'
                ? function(ComponentRegistry $registry) use ($container): void {
                    $viewRegistry = ContainerDependency::get($container, ViewRegistry::class);
                    $viewRegistry->extensions()->append($registry->viewExtensions());
                    $viewRegistry->paths()->append($registry->viewPaths());
                    $viewRegistry->routeNamespace()->append($registry->viewRouteNamespace());
                }
                : null,
        ));
    }
}
