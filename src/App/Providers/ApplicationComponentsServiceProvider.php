<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\Foundation\ConfigKey;
use Concept\Extensions\Components\Commands\ComponentListCommand;
use Concept\Extensions\Components\Commands\ComponentPublishAssetsCommand;
use Concept\Extensions\Components\ComponentRegistry;
use Concept\Extensions\Components\ComponentsServiceProvider;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Config\Foundation\PathManager;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ApplicationComponentsServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
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

        /** @var list<class-string<ComponentInterface>> $componentClasses */
        $componentClasses = $config->get(ConfigKey::COMPONENTS) ?? [];

        $dispatcher = $container->has(EventDispatcherInterface::class)
            ? $container->get(EventDispatcherInterface::class)
            : null;

        $container->addServiceProvider(new ComponentsServiceProvider(
            root: $this->root,
            componentClasses: $componentClasses,
            dispatcher: $dispatcher instanceof EventDispatcherInterface ? $dispatcher : null,
        ));

        $container->add(ComponentListCommand::class, function() use ($container): ComponentListCommand {
            /** @var ComponentRegistry $registry */
            $registry = $container->get(ComponentRegistry::class);

            return new ComponentListCommand($registry);
        })->setShared(true);

        $container->add(ComponentPublishAssetsCommand::class, function() use ($container): ComponentPublishAssetsCommand {
            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ComponentRegistry $registry */
            $registry = $container->get(ComponentRegistry::class);

            return new ComponentPublishAssetsCommand($pathManager, $registry);
        })->setShared(true);
    }
}
