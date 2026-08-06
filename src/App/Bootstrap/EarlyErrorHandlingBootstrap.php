<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Http\Error\Handlers\FallbackFileHandler;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\Handlers\ReportExceptionHandler;
use Concept\Stack\Bricks\ErrorHandling\Reporting\PhpErrorLogReporter;
use League\Container\Container;
use Whoops\Handler\Handler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

/**
 * Registers Whoops before the rest of the container (fatals during later boot).
 */
final class EarlyErrorHandlingBootstrap
{
    public function __construct(
        private readonly bool $debug,
        private readonly string $fallbackFilePath,
    ) {}

    public function boot(Container $container): void
    {
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
