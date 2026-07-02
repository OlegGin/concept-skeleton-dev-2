<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\View\Support\ViewRouteNamespaceResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RenderHttpErrorMiddleware implements MiddlewareInterface
{
    private const string DEFAULT_NAMESPACE = 'frontend';
    private const string TEMPLATE_FORMAT = '@%s/errors/%d';
    private const string NOT_FOUND_MESSAGE = 'Not Found';

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly RequestFormat $requestFormat,
        private readonly ViewRouteNamespaceResolver $routeNamespaceResolver,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->render($request, HttpStatusCode::NOT_FOUND, self::NOT_FOUND_MESSAGE);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors
     */
    public function render(
        ServerRequestInterface $request,
        int $code,
        ?string $message = null,
        array $data = [],
        array $errors = [],
    ): ResponseInterface {
        $message ??= HttpStatusCode::getReasonPhrase($code);

        if ($this->requestFormat->expectsJson($request)) {
            return $this->responseFactory->jsonError($message, $code, $errors);
        }

        $namespace = $this->routeNamespaceResolver->resolve($request) ?? self::DEFAULT_NAMESPACE;
        $template = sprintf(self::TEMPLATE_FORMAT, $namespace, $code);

        return $this->viewResponse->create($template, $data, $code);
    }
}
