<?php declare(strict_types=1);

namespace Concept\Extensions\ConsoleSymfony;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Throwable;

class ConsoleSymfonyServiceProvider extends AbstractServiceProvider
{
    private const string DEFAULT_NAME = 'Console';
    private const string DEFAULT_VERSION = '1.0.0';

    /**
     * @param list<class-string<Command>> $commands
     */
    public function __construct(
        private readonly string $appName,
        private readonly string $appVersion,
        private readonly array $commands,
    ) {}

    public function provides(string $id): bool
    {
        return $id === ConsoleApplication::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ConsoleApplication::class, function () use ($container): ConsoleApplication {
            $consoleApplication = new ConsoleApplication(
                $this->appName !== '' ? $this->appName : self::DEFAULT_NAME,
                $this->appVersion !== '' ? $this->appVersion : self::DEFAULT_VERSION,
            );
            $this->addConsoleCommands($consoleApplication, $container);

            return $consoleApplication;
        })->setShared(true);
    }

    private function addConsoleCommands(ConsoleApplication $consoleApplication, \Psr\Container\ContainerInterface $container): void
    {
        foreach ($this->commands as $className) {
            try {
                /** @var Command $commandInstance */
                $commandInstance = $container->get($className);
                $consoleApplication->addCommand($commandInstance);
            } catch (Throwable $e) {
                $consoleApplication->addCommand(new DisabledCommand($className, $e->getMessage()));
            }
        }
    }
}
