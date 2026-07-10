<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\ValidationRakit\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** API: validation errors → always 422 JSON (no session / flash). */
final class HandleJsonValidationExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $e) {
            return $this->responseFactory->jsonError(
                $e->getMessage(),
                HttpStatusCode::UNPROCESSABLE_ENTITY,
                $e->getErrors(),
            );
        }
    }
}
