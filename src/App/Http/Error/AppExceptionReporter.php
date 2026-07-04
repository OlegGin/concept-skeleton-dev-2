<?php declare(strict_types=1);

namespace Concept\App\Http\Error;

use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Logging\PhpErrorLogWriter;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class AppExceptionReporter implements ExceptionReporterInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PhpErrorLogWriter $phpErrorLogWriter = new PhpErrorLogWriter(),
        private readonly ?ServerRequestInterface $request = null,
    ) {}

    public function report(Throwable $exception): void
    {
        $uri = $this->request?->getUri()->getPath() ?? '';
        $this->phpErrorLogWriter->write(
            $exception,
            $uri,
        );

        $this->logger->exception(
            $exception,
            $uri,
        );
    }
}
