<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HandleNotFoundMiddleware implements MiddlewareInterface
{
    private const string NOT_FOUND_MESSAGE = 'Not Found';

    public function __construct(
        private readonly HttpErrorRendererInterface $httpErrorRenderer,
        private readonly ExceptionReporterInterface $exceptionReporter,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $exception = new NotFoundException(self::NOT_FOUND_MESSAGE);
        $this->exceptionReporter->report($exception);

        return $this->httpErrorRenderer->render($request, HttpStatusCode::NOT_FOUND, self::NOT_FOUND_MESSAGE);
    }
}
