<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\Foundation\ConfigKey;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Components\ComponentsServiceProvider;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ApplicationComponentsServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
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
        $componentClasses = $config->get(ConfigKey::COMPONENTS) ?? [];

        $container->addServiceProvider(new ComponentsServiceProvider(
            componentClasses: $componentClasses,
        ));
    }
}
