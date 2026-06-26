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

        $container->addServiceProvider(new ComponentsServiceProvider(
            root: $this->root,
            componentClasses: $componentClasses,
        ));
    }
}
