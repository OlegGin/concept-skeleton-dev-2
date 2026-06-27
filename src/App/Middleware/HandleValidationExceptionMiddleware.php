<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Foundation\SessionKey;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\ValidationRakit\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HandleValidationExceptionMiddleware implements MiddlewareInterface
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
        } catch (ValidationException $e) {
            if ($this->requestFormat->expectsJson($request)) {
                return $this->responseFactory->jsonError(
                    $e->getMessage(),
                    HttpStatusCode::UNPROCESSABLE_ENTITY,
                    $e->getErrors(),
                );
            }

            $this->flashBag->addError($e->getMessage());
            $this->flashBag->set(SessionKey::VALIDATION_ERRORS, $e->getErrors());
            $this->flashBag->set(SessionKey::VALIDATION_DATA, $e->getOldData());

            return $this->responseFactory->redirectBack(request: $request);
        }
    }
}
