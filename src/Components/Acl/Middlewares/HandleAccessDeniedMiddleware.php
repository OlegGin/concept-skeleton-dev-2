<?php declare(strict_types=1);

namespace Concept\Components\Acl\Middlewares;

use Concept\Components\Acl\Authorization\Exceptions\AccessDeniedException;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HandleAccessDeniedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly RequestFormat $requestFormat,
        private readonly FlashBagInterface $flashBag,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (AccessDeniedException $exception) {
            if ($this->requestFormat->expectsJson($request)) {
                return $this->responseFactory->jsonError(
                    $exception->getMessage(),
                    HttpStatusCode::FORBIDDEN
                );
            }

            $this->flashBag->addError($exception->getMessage());

            return $this->responseFactory->redirectByName($exception->redirectRouteName());
        }
    }
}
