<?php declare(strict_types=1);

namespace Concept\App\Http\Error;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HttpErrorHandler
{
    private const string NOT_FOUND_MESSAGE = 'Not Found';

    public function __construct(
        private readonly HttpErrorRendererInterface $httpErrorRenderer,
        private readonly ExceptionReporterInterface $exceptionReporter,
    ) {}

    public function notFound(ServerRequestInterface $request, ?string $message = null, ?NotFoundException $exception = null): ResponseInterface
    {
        $message ??= $exception?->getMessage() ?? self::NOT_FOUND_MESSAGE;
        $this->exceptionReporter->report($exception ?? new NotFoundException($message));

        return $this->httpErrorRenderer->render($request, HttpStatusCode::NOT_FOUND, $message);
    }

    public function fromThrowable(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        if ($exception instanceof NotFoundException) {
            return $this->notFound($request, exception: $exception);
        }

        $this->exceptionReporter->report($exception);

        if ($exception instanceof HttpErrorException) {
            return $this->httpErrorRenderer->render(
                $request,
                $exception->getStatusCode(),
                $exception->getMessage(),
            );
        }

        return $this->httpErrorRenderer->render(
            $request,
            HttpStatusCode::INTERNAL_SERVER_ERROR,
            HttpStatusCode::getReasonPhrase(HttpStatusCode::INTERNAL_SERVER_ERROR),
        );
    }
}
