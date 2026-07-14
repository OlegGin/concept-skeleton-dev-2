<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Requests\TestEchoRequest;
use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\DatabaseEloquent\Contracts\DatabaseInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Minimal controller for Concept Stack smoke testing (no config).
 */
final class StackTestController
{
    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly LoggerInterface $logger,
        private readonly SessionInterface $session,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly DatabaseInterface $database,
        private readonly ViewResponseFactoryInterface $view,
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
            'handlers' => [
                'toRotatingFile',
                'LogHandlerRegistry (extras)',
            ],
            'file' => 'storage/logs/stack-*.log',
            'providers' => [
                'LoggingStackProvider',
                'MaskingStackProvider',
                'LoggerMonologServiceProvider',
            ],
        ]);
    }

    public function session(): ResponseInterface
    {
        $this->session->set('stack_smoke', 'ok');

        return $this->response->jsonSuccess([
            'session' => [
                'started' => $this->session->isStarted(),
                'id' => $this->session->getId(),
                'stack_smoke' => $this->session->get('stack_smoke'),
            ],
            'csrf' => [
                'token' => $this->csrf->getToken(),
            ],
            'providers' => [
                'SessionStackProvider',
                'SessionServiceProvider',
                'CsrfServiceProvider',
            ],
            'note' => 'VerifyCsrfTokenMiddleware stays in routes when needed — not registered by stack.',
        ]);
    }

    public function db(): ResponseInterface
    {
        $pages = $this->database->capsule()->table('pages');

        return $this->response->jsonSuccess([
            'connection' => 'ok',
            'pages_total' => $pages->count(),
            'pages_published' => $pages->where('published', true)->count(),
            'providers' => [
                'DatabaseStackProvider',
                'DatabaseEloquentServiceProvider',
                'PaginationConfiguratorServiceProvider',
            ],
        ]);
    }

    public function view(): ResponseInterface
    {
        return $this->view->create('@stack/smoke', [
            'message' => 'Stack Twig view is working.',
        ]);
    }
}
