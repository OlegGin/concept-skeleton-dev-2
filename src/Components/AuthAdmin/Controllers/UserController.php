<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Controllers;

use Concept\Components\AuthAdmin\Constants\RouteName;
use Concept\Components\AuthAdmin\Constants\ViewName;
use Concept\Components\AuthAdmin\Enums\UserStatus;
use Concept\Components\AuthAdmin\Requests\Users\StoreUserRequest;
use Concept\Components\AuthAdmin\Requests\Users\UpdateUserPasswordRequest;
use Concept\Components\AuthAdmin\Requests\Users\UpdateUserRequest;
use Concept\Components\AuthAdmin\Dto\User\StoreUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserPasswordDto;
use Concept\App\Models\BaseModel;
use Concept\Components\AuthAdmin\Mappers\UserAttributesMapper;
use Concept\Components\AuthAdmin\Models\UserModel;
use Concept\Components\AuthAdmin\Services\AuthService;
use Concept\Components\AuthAdmin\Services\UserService;
use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class UserController
{
    private const string MSG_USER_NOT_FOUND = 'User not found';
    private const string MSG_USER_CREATED = 'User successfully created';
    private const string MSG_USER_UPDATED = 'User successfully updated';
    private const string MSG_PASSWORD_UPDATED = 'Password updated successfully.';

    private const string CONFIG_PAGINATION_PER_PAGE = 'pagination.per_page';
    private const int DEFAULT_PAGINATION_PER_PAGE = 10;

    private const string CONTEXT_USERS = 'users';
    private const string CONTEXT_USER = 'user';
    private const string CONTEXT_STATUSES = 'statuses';
    private const string CONTEXT_ACL_ROLES = 'acl_roles';
    private const string CONTEXT_ID = 'id';

    private const int API_TOKEN_BYTES = 32;

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly ConfigInterface $config,
        private readonly UserService $userService,
        private readonly FlashBagInterface $flashBag,
        private readonly UserModel $userModel,
        private readonly AclRoleModel $aclRoleModel,
    ) {}

    public function index(): ResponseInterface
    {
        $users = $this->userModel
            ->newQuery()
            ->orderBy(UserModel::FIELD_CREATED_AT, BaseModel::SORT_DESC)
            ->paginate($this->config->getInt(self::CONFIG_PAGINATION_PER_PAGE, self::DEFAULT_PAGINATION_PER_PAGE))
            ->withQueryString();

        return $this->viewResponse->create(ViewName::USERS_LIST, [self::CONTEXT_USERS => $users]);
    }

    public function show(int $id): ResponseInterface
    {
        $user = $this->userService->findById($id);

        if (!($user instanceof UserModel)) {
            $this->flashBag->addError(self::MSG_USER_NOT_FOUND);

            return $this->response->redirectByName(RouteName::USERS);
        }

        return $this->viewResponse->create(ViewName::USERS_SHOW, [self::CONTEXT_USER => $user]);
    }

    public function create(): ResponseInterface
    {
        return $this->viewResponse->create(ViewName::USERS_CREATE, [
            self::CONTEXT_STATUSES => UserStatus::cases(),
            self::CONTEXT_ACL_ROLES => $this->aclRolesForSelect(),
        ]);
    }

    public function store(StoreUserRequest $request): ResponseInterface
    {
        /** @var StoreUserDto $dto */
        $dto = $request->toDto();
        $this->userService->create($dto);
        $this->flashBag->addSuccess(self::MSG_USER_CREATED);

        return $this->response->redirectByName(RouteName::USERS);
    }

    public function edit(int $id): ResponseInterface
    {
        $user = $this->userService->findById($id);
        if (!($user instanceof UserModel)) {
            $this->flashBag->addError(self::MSG_USER_NOT_FOUND);

            return $this->response->redirectByName(RouteName::USERS);
        }

        return $this->viewResponse->create(ViewName::USERS_EDIT, [
            self::CONTEXT_USER => $user,
            self::CONTEXT_STATUSES => UserStatus::cases(),
            self::CONTEXT_ACL_ROLES => $this->aclRolesForSelect(),
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): ResponseInterface
    {
        $user = $this->userService->findById($id);

        if (!($user instanceof UserModel)) {
            $this->flashBag->addError(self::MSG_USER_NOT_FOUND);

            return $this->response->redirectByName(RouteName::USERS);
        }

        /** @var UpdateUserDto $dto */
        $dto = $request->toDto();
        $this->userService->update($user, $dto);
        $this->flashBag->addSuccess(self::MSG_USER_UPDATED);

        return $this->response->redirectByName(RouteName::USERS);
    }

    public function updatePassword(UpdateUserPasswordRequest $request, int $id): ResponseInterface
    {
        $user = $this->userService->findById($id);

        if (!($user instanceof UserModel)) {
            $this->flashBag->addError(self::MSG_USER_NOT_FOUND);

            return $this->response->redirectByName(RouteName::USERS);
        }

        /** @var UpdateUserPasswordDto $data */
        $data = $request->toDto();

        $this->userService->updatePassword($user, $data);

        $this->flashBag->addSuccess(self::MSG_PASSWORD_UPDATED);

        return $this->response->redirectByName(RouteName::USER_EDIT, [self::CONTEXT_ID => $id]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $user = $this->userService->findById($id);

        if (!($user instanceof UserModel)) {
            $this->flashBag->addError(self::MSG_USER_NOT_FOUND);

            return $this->response->redirectByName(RouteName::USERS);
        }

        try {
            $this->userService->delete($user);
        } catch (\Throwable $e) {
            $this->flashBag->addError($e->getMessage());

            return $this->response->redirectByName(RouteName::USERS);
        }

        return $this->response->redirectByName(RouteName::USERS);
    }

    public function generateTokenApi(): ResponseInterface
    {
        return $this->response->json([
            'token' => bin2hex(random_bytes(self::API_TOKEN_BYTES)),
        ]);
    }

    /**
     * @return list<AclRoleModel>
     */
    private function aclRolesForSelect(): array
    {
        /** @var list<AclRoleModel> $roles */
        $roles = $this->aclRoleModel
            ->newQuery()
            ->orderBy(AclRoleModel::FIELD_NAME)
            ->get()
            ->all();

        return $roles;
    }
}
