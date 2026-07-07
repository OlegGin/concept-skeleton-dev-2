<?php declare(strict_types=1);

namespace Concept\App\Http\Error;

use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use League\Container\DefinitionContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class AppExceptionReporter implements ExceptionReporterInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DefinitionContainerInterface $container,
        private readonly PhpErrorLogWriter $phpErrorLogWriter = new PhpErrorLogWriter(),
    ) {}

    public function report(Throwable $exception): void
    {
        $uri = $this->resolveRequestUri();
        $this->phpErrorLogWriter->write(
            $exception,
            $uri,
        );

        $this->logger->exception(
            $exception,
            $uri,
        );
    }

    private function resolveRequestUri(): string
    {
        if (!$this->container->has(ServerRequestInterface::class)) {
            return '';
        }

        $request = ContainerDependency::get($this->container, ServerRequestInterface::class);

        return $request->getUri()->getPath();
    }
}
