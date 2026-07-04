<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use League\Route\Http\Exception\NotFoundException;
use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HandleHttpErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RenderHttpErrorMiddleware $renderHttpError,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundException $exception) {
            return $this->renderHttpError->render(
                $request,
                HttpStatusCode::NOT_FOUND,
                $exception->getMessage(),
            );
        } catch (HttpErrorException $exception) {
            return $this->renderHttpError->render(
                $request,
                $exception->getStatusCode(),
                $exception->getMessage(),
            );
        } catch (Throwable) {
            return $this->renderHttpError->render(
                $request,
                HttpStatusCode::INTERNAL_SERVER_ERROR,
                HttpStatusCode::getReasonPhrase(HttpStatusCode::INTERNAL_SERVER_ERROR),
            );
        }
    }
}
