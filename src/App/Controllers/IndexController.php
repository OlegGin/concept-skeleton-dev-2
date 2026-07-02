<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function __construct(
        private readonly ViewResponseFactoryInterface $responseFactory,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->responseFactory->create('frontend/test');
    }
}
