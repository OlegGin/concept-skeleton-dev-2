<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RenderHttpErrorMiddleware implements MiddlewareInterface
{
    private const string NOT_FOUND_MESSAGE = 'Not Found';

    public function __construct(
        private readonly HttpErrorRendererInterface $httpErrorRenderer,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->httpErrorRenderer->render($request, HttpStatusCode::NOT_FOUND, self::NOT_FOUND_MESSAGE);
    }
}
