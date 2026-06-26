<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Controllers;

use Concept\Components\AuthAdmin\Constants\RouteName;
use Concept\Components\AuthAdmin\Constants\ViewName;
use Concept\Components\AuthAdmin\Dto\LoginDto;
use Concept\Components\AuthAdmin\Requests\LoginRequest;
use Concept\Components\AuthAdmin\Services\AuthService;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class AdminController
{
    private const string MSG_WELCOME = 'Welcome back, %s';
    private const string MSG_LOGOUT = 'You have been logged out.';
    private const string MSG_AUTH_ERROR = 'Incorrect email or password';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly FlashBagInterface $flashBag,
        private readonly AuthService $auth
    ) {}

    public function index(): ResponseInterface
    {
        return $this->viewResponse->create('@dashboard/index');
    }

    public function showLogin(): ResponseInterface
    {
        return $this->viewResponse->create(ViewName::LOGIN);
    }

    public function login(LoginRequest $request): ResponseInterface
    {
        /** @var LoginDto $loginDto */
        $loginDto = $request->toDto();
        $success = $this->auth->attempt($loginDto->email, $loginDto->password, $loginDto->remember);
        if ($success) {
            $user = $this->auth->user();
            $welcomeMessage = sprintf(self::MSG_WELCOME, $user?->getName() ?? 'User');
            $this->flashBag->addSuccess($welcomeMessage);

            return $this->response->redirectByName(RouteName::ADMIN_DASHBOARD);
        }

        $this->flashBag->addError(self::MSG_AUTH_ERROR);

        return $this->response->redirectByName(RouteName::ADMIN_LOGIN);
    }

    public function logout(): ResponseInterface
    {
        $this->auth->logout();
        $this->flashBag->addInfo(self::MSG_LOGOUT);

        return $this->response->redirectByName(RouteName::ADMIN_LOGIN);
    }
}