<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HandleHttpErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly HttpErrorRendererInterface $httpErrorRenderer,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundException $exception) {
            return $this->httpErrorRenderer->render(
                $request,
                HttpStatusCode::NOT_FOUND,
                $exception->getMessage(),
            );
        } catch (HttpErrorException $exception) {
            return $this->httpErrorRenderer->render(
                $request,
                $exception->getStatusCode(),
                $exception->getMessage(),
            );
        } catch (Throwable) {
            return $this->httpErrorRenderer->render(
                $request,
                HttpStatusCode::INTERNAL_SERVER_ERROR,
                HttpStatusCode::getReasonPhrase(HttpStatusCode::INTERNAL_SERVER_ERROR),
            );
        }
    }
}
