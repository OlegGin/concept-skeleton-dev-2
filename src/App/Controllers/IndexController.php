<?php declare(strict_types=1);

namespace Concept\App\Controllers;

use Concept\App\Http\Dto\LoginDto;
use Concept\App\Http\LoginFormResponse;
use Concept\App\Http\Requests\LoginRequest;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function index(): ResponseInterface
    {
        return LoginFormResponse::create();
    }

    public function login(LoginRequest $request): ResponseInterface
    {
        $dto = $request->toDto();
        if (!$dto instanceof LoginDto) {
            return LoginFormResponse::create(success: 'Validated, but DTO mapping failed.');
        }

        return LoginFormResponse::create(
            success: sprintf('Welcome, %s! (Dto: %s)', $dto->email, $dto::class),
        );
    }
}
