<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\View\Contracts\ViewInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ViewInterface $view,
    ) {}

    public function index(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($this->view->render('frontend/test'));

        return $response;
    }
}
