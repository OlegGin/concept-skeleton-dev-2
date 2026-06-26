<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Middlewares;

use Concept\Components\AuthAdmin\Constants\RouteName;
use Concept\Components\AuthAdmin\Enums\UserStatus;
use Concept\Components\AuthAdmin\Services\AuthService;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Concept\Core\Services\Session\Contracts\FlashBagInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    private const string MSG_LOGIN_FAILED = 'Login failed. Please try again.';

    public function __construct(
        private readonly AuthService $auth,
        private readonly FlashBagInterface $flashBag,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->auth->check()) {
            return $this->responseFactory->redirectByName(RouteName::ADMIN_LOGIN);
        }

        $user = $this->auth->user();
        if (!$user?->isAdmin() || ($user->getStatus() !== UserStatus::ACTIVE->value)) {
            $this->auth->logout();
            $this->flashBag->addError(self::MSG_LOGIN_FAILED);

            return $this->responseFactory->redirectByName(RouteName::ADMIN_LOGIN);
        }

        return $handler->handle($request);
    }
}
