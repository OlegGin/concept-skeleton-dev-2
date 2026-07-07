<?php declare(strict_types=1);

namespace Concept\App\Http\Error\Handlers;

use Closure;
use Concept\Core\Container\ContainerDependency;
use Concept\App\Http\Error\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use League\Container\DefinitionContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Whoops\Handler\Handler;

final class RenderHttpErrorHandler extends Handler
{
    private const int MIN_ERROR_CODE = 400;
    private const int MAX_ERROR_CODE = 599;
    private const int DEFAULT_ERROR_CODE = HttpStatusCode::INTERNAL_SERVER_ERROR;

    /**
     * @param Closure(): HttpErrorRendererInterface $rendererFactory
     */
    public function __construct(
        private readonly Closure $rendererFactory,
        private readonly DefinitionContainerInterface $container,
    ) {}

    public function handle(): int
    {
        try {
            if (!$this->container->has(ServerRequestInterface::class)) {
                return Handler::DONE;
            }

            $request = ContainerDependency::get($this->container, ServerRequestInterface::class);
            $exception = $this->getException();
            $response = ($this->rendererFactory)()->render(
                $request,
                $this->resolveStatusCode($exception),
                $exception->getMessage(),
            );
            $this->emit($response);
        } catch (Throwable) {
            return Handler::DONE;
        }

        return Handler::QUIT;
    }

    private function resolveStatusCode(Throwable $exception): int
    {
        $code = $exception->getCode();
        if (method_exists($exception, 'getStatusCode')) {
            $code = $exception->getStatusCode();
        }

        if (!is_numeric($code) || $code < self::MIN_ERROR_CODE || $code > self::MAX_ERROR_CODE) {
            $code = self::DEFAULT_ERROR_CODE;
        }

        return (int) $code;
    }

    private function emit(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        $run = $this->getRun();
        if ($run !== null) {
            $run->sendHttpCode($statusCode);
        } elseif (!headers_sent()) {
            http_response_code($statusCode);
        }

        if (!headers_sent()) {
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        echo (string) $response->getBody();
    }
}
