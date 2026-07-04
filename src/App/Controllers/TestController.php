<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class TestController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
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
}
