<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Dto\LoginDto;
use Concept\App\Http\Requests\LoginRequest;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function __construct(
        private readonly ViewResponseFactoryInterface $view,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->view->create('home');
    }

    public function login(LoginRequest $form): ResponseInterface
    {
        $dto = $form->toDto();
        if (!$dto instanceof LoginDto) {
            return $this->view->create('home', [
                'success' => 'Validated, but DTO mapping failed.',
            ]);
        }

        return $this->view->create('home', [
            'success' => sprintf('Welcome, %s! (Dto: %s)', $dto->email, $dto::class),
        ]);
    }
}
