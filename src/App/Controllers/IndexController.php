<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\Extensions\Http\Response\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
    ) {}

    public function index(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write('Hello, world!');

        return $response;
    }
}
