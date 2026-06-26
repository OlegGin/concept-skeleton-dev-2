<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Services;

use Concept\Components\AuthAdmin\Dto\User\StoreUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserPasswordDto;
use Concept\Components\AuthAdmin\Mappers\UserAttributesMapper;
use Concept\Components\AuthAdmin\Models\UserModel;
use Exception;

class UserService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserAttributesMapper $userAttributesMapper,
        private readonly AuthService $authService
    ) {}

    public function create(StoreUserDto $dto): UserModel
    {
        /** @var UserModel $user */
        $user = $this->userModel->newQuery()->create($this->userAttributesMapper->fromStore($dto));

        return $user;
    }

    public function update(UserModel $user, UpdateUserDto $dto): bool
    {
        return $user->update($this->userAttributesMapper->fromUpdate($dto));
    }

    public function updatePassword(UserModel $user, UpdateUserPasswordDto $dto): bool
    {
        return $user->update($this->userAttributesMapper->fromPasswordUpdate($dto));
    }

    public function delete(UserModel $user): bool
    {
        if (!empty($this->authService->user()) && $user->getId() == $this->authService->user()->getId()) {
            throw new Exception('Cannot delete self.');
        }

        return (bool)$user->delete();
    }

    public function findById(int $id): ?UserModel
    {
        /** @var UserModel|null $user */
        $user = $this->userModel->newQuery()->with('aclRole')->find($id);

        return $user;
    }
}