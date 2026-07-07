<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class ErrorTestController
{
    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponse,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->viewResponse->create('@frontend/errors-test');
    }
}
