<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexController
{
    public function __construct(
        private readonly ResponseFactoryInterface $response
    ) {}

    public function index(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write('body');

        return $response;
    }

    public function edit(ServerRequestInterface $request, int $id): ResponseInterface
    {
        var_dump($id, $request->getAttributes());

        $response = new Response();
        $response->getBody()->write('body');

        return $response;
    }
}