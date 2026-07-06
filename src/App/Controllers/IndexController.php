<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Exception\HttpErrorException;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function __construct(
//        private readonly ConfigInterface $config,
        private readonly ViewResponseFactoryInterface $responseFactory,
    ) {}

    public function index(): ResponseInterface
    {
//        var_dump($this->config->all()); die();

        return $this->responseFactory->create('@frontend/index');
    }
}
