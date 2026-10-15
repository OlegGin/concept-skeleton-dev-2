<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Services;

use Concept\Components\AuthAdmin\Events\UserLoginFailed;
use Concept\Components\AuthAdmin\Events\UserLoginSucceeded;
use Concept\Components\AuthAdmin\Models\UserModel;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AuthService
{
    private const string SESSION_KEY = 'auth_user_id';

    private ?UserModel $user = null;

    public function __construct(
        private readonly SessionInterface $session,
        private readonly UserModel $userModel,
        private readonly ?EventDispatcherInterface $dispatcher = null,
    ) {}

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = $this->userModel
            ->newQuery()
            ->where(UserModel::FIELD_EMAIL, $email)
            ->first();

        if (!$user || !$user->verifyPassword($password)) {
            $this->dispatcher?->dispatch(new UserLoginFailed(email: $email));

            return false;
        }

        $this->login($user, $remember);
        $this->dispatcher?->dispatch(new UserLoginSucceeded(
            userId: $user->getId(),
            email: $user->getEmail(),
        ));

        return true;
    }

    public function user(): ?UserModel
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->session->get(self::SESSION_KEY);
        if (!$id || !is_numeric($id)) {
            return null;
        }

        $user = $this->userModel
            ->newQuery()
            ->where(UserModel::FIELD_ID, $id)
            ->first();

        if (!$user) {
            $this->logout();

            return null;
        }

        return $this->user = $user;
    }

    public function check(): bool
    {
        return $this->session->has(self::SESSION_KEY);
    }

    public function login(UserModel $user, bool $remember = false): void
    {
        $this->session->migrate(true);
        $this->session->set(self::SESSION_KEY, $user->getId());
        $this->user = $user;

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $user->setAttribute(UserModel::FIELD_REMEMBER_TOKEN, $token);
            $user->save();
        }
    }

    public function logout(): void
    {
        $this->session->remove(self::SESSION_KEY);
        $this->session->migrate(true);
        $this->user = null;
    }
}