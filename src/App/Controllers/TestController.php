<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\App\Http\Requests\TestEchoRequest;
use Concept\Extensions\DatabaseEloquent\Contracts\DatabaseInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class TestController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly RequestFormat $requestFormat,
        private readonly DatabaseInterface $database,
    ) {}

    public function boom(): never
    {
        throw new RuntimeException('Intentional server error for stack testing.');
    }

    public function httpError(): never
    {
        throw new HttpErrorException('Service unavailable (test).', HttpStatusCode::SERVICE_UNAVAILABLE);
    }

    public function hello(ServerRequestInterface $request, string $name): ResponseInterface
    {
        return $this->responseFactory->json([
            'message' => sprintf('Hello, %s.', $name),
            'path' => $request->getUri()->getPath(),
            'resolvers' => ['ServerRequestArgumentResolver', 'RouteParameterArgumentResolver'],
        ]);
    }

    public function user(int $id): ResponseInterface
    {
        return $this->responseFactory->json([
            'user_id' => $id,
            'type' => get_debug_type($id),
            'resolvers' => ['TypedRouteParameterArgumentResolver', 'RouteParameterArgumentResolver'],
        ]);
    }

    public function echo(TestEchoRequest $request): ResponseInterface
    {
        $validated = $request->validated();

        if ($this->requestFormat->expectsJson($request->httpRequest())) {
            return $this->responseFactory->json([
                'validated' => $validated,
                'resolvers' => ['FormRequestArgumentResolver', 'ValidationServiceProvider'],
            ]);
        }

        return $this->viewResponse->create('@frontend/echo-result', [
            'validated' => $validated,
        ]);
    }

    public function db(): ResponseInterface
    {
        $pages = $this->database->capsule()->table('pages');

        return $this->responseFactory->json([
            'connection' => 'ok',
            'pages_total' => $pages->count(),
            'pages_published' => $pages->where('published', true)->count(),
            'resolvers' => ['DatabaseEloquentServiceProvider', 'DatabaseInterface'],
        ]);
    }
}
