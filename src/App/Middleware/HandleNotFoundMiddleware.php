<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Http\Error\HttpErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HandleNotFoundMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly HttpErrorHandler $httpErrorHandler,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->httpErrorHandler->notFound($request);
    }
}
