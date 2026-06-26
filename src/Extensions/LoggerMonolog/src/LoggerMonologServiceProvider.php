<?php declare(strict_types=1);

namespace Concept\Extensions\LoggerMonolog;

use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Monolog\Processor\PsrLogMessageProcessor;
use Throwable;

final class LoggerMonologServiceProvider extends AbstractServiceProvider
{
    public function __construct(
        private readonly string $path,
        private readonly string $level,
        private readonly int $maxFiles,
        private readonly string $channel,
        private readonly ?HandlerInterface $telemetryHandler = null,
    ) {}

    public function provides(string $id): bool
    {
        return $id === LoggerInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(LoggerInterface::class, function() use ($container): Logger {
            $monolog = new Monolog($this->channel);
            $this->setup($monolog);

            /** @var DataMaskerInterface|null $masker */
            $masker = $container->has(DataMaskerInterface::class)
                ? $container->get(DataMaskerInterface::class)
                : null;

            return new Logger($monolog, $masker);
        })->setShared(true);
    }

    private function setup(Monolog $monolog): void
    {
        try {
            /** @phpstan-ignore-next-line */
            $logLevel = Level::fromName($this->level);
        } catch (Throwable) {
            $logLevel = Level::Debug;
        }

        $monolog->pushHandler(new RotatingFileHandler($this->path, $this->maxFiles, $logLevel));
        if ($this->telemetryHandler !== null) {
            $monolog->pushHandler($this->telemetryHandler);
        }
        $monolog->pushProcessor(new PsrLogMessageProcessor());
    }
}
