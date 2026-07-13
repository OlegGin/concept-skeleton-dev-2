<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Requests\TestEchoRequest;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Minimal controller for Concept Stack smoke testing (no config, no view, no db).
 */
final class StackTestController
{
    public function __construct(
        private readonly ResponseFactoryInterface $response,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->response->jsonSuccess([
            'stack' => 'concept-stack',
            'message' => 'Stack HTTP is working.',
        ]);
    }

    public function ping(): ResponseInterface
    {
        return $this->response->jsonSuccess(['message' => 'pong']);
    }

    public function hello(ServerRequestInterface $request, string $name): ResponseInterface
    {
        return $this->response->jsonSuccess([
            'message' => sprintf('Hello, %s.', $name),
            'path' => $request->getUri()->getPath(),
            'resolvers' => [
                'ServerRequestArgumentResolver',
                'TypedRouteParameterArgumentResolver',
                'RouteParameterArgumentResolver',
            ],
        ]);
    }

    public function user(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->response->jsonSuccess([
            'user_id' => $id,
            'type' => get_debug_type($id),
            'path' => $request->getUri()->getPath(),
            'casting' => 'TypedRouteParameterArgumentResolver',
        ]);
    }

    public function echo(TestEchoRequest $request): ResponseInterface
    {
        return $this->response->jsonSuccess([
            'validated' => $request->validated(),
            'resolvers' => [
                'FormRequestArgumentResolver',
                'ValidationServiceProvider',
            ],
        ]);
    }
}
