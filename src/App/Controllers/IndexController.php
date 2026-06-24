<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexController
{
    public function __construct() {}

    public function index(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write('body');

        return $response;
    }

    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        var_dump($request->getAttributes());

        $response = new Response();
        $response->getBody()->write('body');

        return $response;
    }
}