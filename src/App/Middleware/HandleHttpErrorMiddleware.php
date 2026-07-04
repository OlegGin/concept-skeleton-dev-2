<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\ExceptionReporterInterface;
use Concept\Extensions\ErrorHandlerWhoops\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\ValidationRakit\Exceptions\ValidationException;
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
        private readonly ExceptionReporterInterface $exceptionReporter,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly RequestFormat $requestFormat,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundException $exception) {
            $this->exceptionReporter->report($exception);

            return $this->httpErrorRenderer->render(
                $request,
                HttpStatusCode::NOT_FOUND,
                $exception->getMessage(),
            );
        } catch (HttpErrorException $exception) {
            $this->exceptionReporter->report($exception);

            return $this->httpErrorRenderer->render(
                $request,
                $exception->getStatusCode(),
                $exception->getMessage(),
            );
        } catch (ValidationException $exception) {
            if ($this->requestFormat->expectsJson($request)) {
                return $this->responseFactory->jsonError(
                    $exception->getMessage(),
                    HttpStatusCode::UNPROCESSABLE_ENTITY,
                    $exception->getErrors(),
                );
            }

            return $this->httpErrorRenderer->render(
                $request,
                HttpStatusCode::UNPROCESSABLE_ENTITY,
                $exception->getMessage(),
                [
                    'errors' => $exception->getErrors(),
                    'old' => $exception->getOldData(),
                ],
            );
        } catch (Throwable $exception) {
            $this->exceptionReporter->report($exception);

            return $this->httpErrorRenderer->render(
                $request,
                HttpStatusCode::INTERNAL_SERVER_ERROR,
                HttpStatusCode::getReasonPhrase(HttpStatusCode::INTERNAL_SERVER_ERROR),
            );
        }
    }
}
