<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\ConsoleSymfony\ConsoleSymfonyServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Symfony\Component\Console\Command\Command;

final class ConsoleLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
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

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        /** @var list<class-string<Command>> $commands */
        $commands = $config->get(ConfigKey::COMMANDS) ?? [];
        $container->addServiceProvider(new ConsoleSymfonyServiceProvider(
            appName: $config->getString(ConfigKey::APP_NAME),
            appVersion: $config->getString(ConfigKey::APP_VERSION),
            commands: $commands,
        ));
    }
}
