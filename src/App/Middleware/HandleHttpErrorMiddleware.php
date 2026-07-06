<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Http\Error\HttpErrorHandler;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HandleHttpErrorMiddleware implements MiddlewareInterface
{
    private readonly bool $debug;

    public function __construct(
        private readonly HttpErrorHandler $httpErrorHandler,
        ConfigInterface $config,
    ) {
        $this->debug = $config->getBool(ConfigKey::APP_DEBUG);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundException $exception) {
            return $this->httpErrorHandler->notFound($request, exception: $exception);
        } catch (Throwable $exception) {
            if ($this->debug) {
                throw $exception;
            }

            return $this->httpErrorHandler->fromThrowable($request, $exception);
        }
    }
}
