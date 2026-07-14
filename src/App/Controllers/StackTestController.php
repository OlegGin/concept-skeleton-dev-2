<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Requests\TestEchoRequest;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Minimal controller for Concept Stack smoke testing (no config, no view, no db).
 */
final class StackTestController
{
    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly LoggerInterface $logger,
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

    public function log(): ResponseInterface
    {
        $this->logger->debug('Stack log smoke: debug', ['step' => 'debug']);
        $this->logger->info('Stack log smoke: info', ['user' => 'stack-tester']);
        $this->logger->warning('Stack log smoke: warning with secrets', [
            'password' => 'secret-password',
            'api_token' => 'secret-token',
            'email' => 'tester@example.com',
        ]);

        return $this->response->jsonSuccess([
            'logged' => true,
            'levels' => ['debug', 'info', 'warning'],
            'masking' => [
                'enabled' => true,
                'keys' => ['password', 'api_token'],
                'expected' => '*** in rotated stack-*.log',
            ],
            'file' => 'storage/logs/stack-*.log',
            'providers' => [
                'LoggingStackProvider',
                'MaskingStackProvider',
                'LoggerMonologServiceProvider',
            ],
        ]);
    }
}
