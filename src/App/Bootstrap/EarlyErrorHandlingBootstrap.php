<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Http\Error\Handlers\FallbackFileHandler;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\Handlers\ReportExceptionHandler;
use Concept\Stack\Bricks\ErrorHandling\Reporting\PhpErrorLogReporter;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

/**
 * Registers Whoops before the rest of the container (fatals during provider boot).
 */
final class EarlyErrorHandlingBootstrap extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function __construct(
        private readonly bool $debug,
        private readonly string $fallbackFilePath,
    ) {}

    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->add(Whoops::class, EarlyWhoopsServiceProvider::register(
            $this->earlyRenderHandler(),
            new ReportExceptionHandler(static fn() => new PhpErrorLogReporter()),
        ))->setShared(true);
    }

    private function earlyRenderHandler(): Handler
    {
        return match (true) {
            $this->debug => new PrettyPageHandler(),
            PHP_SAPI === 'cli' => new PlainTextHandler(),
            default => new FallbackFileHandler($this->fallbackFilePath),
        };
    }
}
