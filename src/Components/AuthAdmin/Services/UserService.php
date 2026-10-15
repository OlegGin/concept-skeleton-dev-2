<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Services;

use Concept\Components\AuthAdmin\Events\UserCreated;
use Concept\Components\AuthAdmin\Events\UserDeleted;
use Concept\Components\AuthAdmin\Events\UserUpdated;
use Concept\Components\AuthAdmin\Dto\User\StoreUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserPasswordDto;
use Concept\Components\AuthAdmin\Mappers\UserAttributesMapper;
use Concept\Components\AuthAdmin\Models\UserModel;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserAttributesMapper $userAttributesMapper,
        private readonly AuthService $authService,
        private readonly ?EventDispatcherInterface $dispatcher = null,
    ) {}

    public function create(StoreUserDto $dto): UserModel
    {
        /** @var UserModel $user */
        $user = $this->userModel->newQuery()->create($this->userAttributesMapper->fromStore($dto));

        $this->dispatcher?->dispatch(new UserCreated(
            userId: $user->getId(),
            email: $user->getEmail(),
            actorUserId: $this->authService->user()?->getId(),
        ));

        return $user;
    }

    public function update(UserModel $user, UpdateUserDto $dto): bool
    {
        $updated = $user->update($this->userAttributesMapper->fromUpdate($dto));
        if ($updated) {
            $this->dispatcher?->dispatch(new UserUpdated(
                userId: $user->getId(),
                email: $user->getEmail(),
                actorUserId: $this->authService->user()?->getId(),
            ));
        }

        return $updated;
    }

    public function updatePassword(UserModel $user, UpdateUserPasswordDto $dto): bool
    {
        $updated = $user->update($this->userAttributesMapper->fromPasswordUpdate($dto));
        if ($updated) {
            $this->dispatcher?->dispatch(new UserUpdated(
                userId: $user->getId(),
                email: $user->getEmail(),
                actorUserId: $this->authService->user()?->getId(),
            ));
        }

        return $updated;
    }

    public function delete(UserModel $user): bool
    {
        if (!empty($this->authService->user()) && $user->getId() == $this->authService->user()->getId()) {
            throw new Exception('Cannot delete self.');
        }

        $userId = $user->getId();
        $email = $user->getEmail();
        $deleted = (bool) $user->delete();
        if ($deleted) {
            $this->dispatcher?->dispatch(new UserDeleted(
                userId: $userId,
                email: $email,
                actorUserId: $this->authService->user()?->getId(),
            ));
        }

        return $deleted;
    }

    public function findById(int $id): ?UserModel
    {
        /** @var UserModel|null $user */
        $user = $this->userModel->newQuery()->with('aclRole')->find($id);

        return $user;
    }
}