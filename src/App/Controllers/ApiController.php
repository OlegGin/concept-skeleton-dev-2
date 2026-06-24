<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiController
{
    public function __construct(
        private readonly ResponseFactoryInterface $response,
    ) {}

    public function ping(): ResponseInterface
    {
        return $this->response->jsonSuccess(['message' => 'pong']);
    }

    public function echo(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        return $this->response->jsonSuccess([
            'method' => $request->getMethod(),
            'received' => is_array($body) ? $body : [],
        ]);
    }
}
