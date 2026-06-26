<?php declare(strict_types=1);

namespace Concept\Components\Acl\Controllers\Admin;

use Concept\Components\Acl\Constants\RouteName;
use Concept\Components\Acl\Constants\ViewName;
use Concept\Components\Acl\Dto\Role\RoleDto;
use Concept\Components\Acl\Mappers\RoleAttributesMapper;
use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Components\Acl\Requests\Roles\StoreRoleRequest;
use Concept\Components\Acl\Requests\Roles\UpdateRoleRequest;
use Concept\Components\Acl\Support\AclListQuery;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RoleController
{
    private const string CONFIG_PAGINATION_PER_PAGE = 'pagination.per_page';
    private const int DEFAULT_PAGINATION_PER_PAGE = 10;

    private const string MSG_ROLE_CREATED = 'Role created successfully.';
    private const string MSG_ROLE_UPDATED = 'Role updated successfully.';
    private const string MSG_CANNOT_DELETE_ROLE = 'Cannot delete a role that has child roles or rules.';
    private const string MSG_ROLE_DELETED = 'Role deleted successfully.';
    private const string MSG_ROLE_NOT_FOUND = 'Role not found.';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly FlashBagInterface $flashBag,
        private readonly ConfigInterface $config,
        private readonly AclRoleModel $roleModel,
        private readonly RoleAttributesMapper $roleAttributesMapper,
        private readonly AclListQuery $listQuery,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $params */
        $params = $request->getQueryParams();

        $filters = $this->listQuery->roleFilters($params);
        [$sortBy, $sortDirection] = $this->listQuery->roleSort($params);
        $query = $this->listQuery->viewContext($filters, $sortBy, $sortDirection);

        $builder = $this->roleModel
            ->newQuery()
            ->with('parent')
            ->withCount(['children', 'rules']);

        $this->listQuery->applyRoleFilters($builder, $filters);
        $this->listQuery->applyRoleSort($builder, $sortBy, $sortDirection);

        $roles = $builder
            ->paginate($this->config->getInt(self::CONFIG_PAGINATION_PER_PAGE, self::DEFAULT_PAGINATION_PER_PAGE))
            ->appends($query);

        return $this->viewResponse->create(ViewName::ROLES_LIST, [
            'roles' => $roles,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'query' => $query,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->viewResponse->create(ViewName::ROLES_CREATE, [
            'roles' => $this->parentOptions(),
        ]);
    }

    public function store(StoreRoleRequest $request): ResponseInterface
    {
        /** @var RoleDto $dto */
        $dto = $request->toDto();
        $this->roleModel->newQuery()->create($this->roleAttributesMapper->toAttributes($dto));
        $this->flashBag->addSuccess(self::MSG_ROLE_CREATED);

        return $this->response->redirectByName(RouteName::ROLES);
    }

    public function edit(int $id): ResponseInterface
    {
        $role = $this->findRole($id);
        if ($role === null) {
            return $this->redirectNotFound();
        }

        return $this->viewResponse->create(ViewName::ROLES_EDIT, [
            'role' => $role,
            'roles' => $this->parentOptions($id),
        ]);
    }

    public function update(UpdateRoleRequest $request, int $id): ResponseInterface
    {
        $role = $this->findRole($id);
        if ($role === null) {
            return $this->redirectNotFound();
        }

        /** @var RoleDto $dto */
        $dto = $request->toDto();
        $role->update($this->roleAttributesMapper->toAttributes($dto));
        $this->flashBag->addSuccess(self::MSG_ROLE_UPDATED);

        return $this->response->redirectByName(RouteName::ROLES);
    }

    public function destroy(int $id): ResponseInterface
    {
        $role = $this->findRole($id);
        if ($role === null) {
            return $this->redirectNotFound();
        }

        if ($role->children()->exists() || $role->rules()->exists()) {
            $this->flashBag->addError(self::MSG_CANNOT_DELETE_ROLE);

            return $this->response->redirectByName(RouteName::ROLES);
        }

        $role->delete();
        $this->flashBag->addSuccess(self::MSG_ROLE_DELETED);

        return $this->response->redirectByName(RouteName::ROLES);
    }

    private function findRole(int $id): ?AclRoleModel
    {
        $role = $this->roleModel->newQuery()->find($id);

        return $role instanceof AclRoleModel ? $role : null;
    }

    /**
     * @return list<AclRoleModel>
     */
    private function parentOptions(?int $excludeId = null): array
    {
        $query = $this->roleModel
            ->newQuery()
            ->orderBy(AclRoleModel::FIELD_NAME);

        if ($excludeId !== null) {
            $query->where(AclRoleModel::FIELD_ID, '!=', $excludeId);
        }

        /** @var list<AclRoleModel> */
        return $query->get()->all();
    }

    private function redirectNotFound(): ResponseInterface
    {
        $this->flashBag->addError(self::MSG_ROLE_NOT_FOUND);

        return $this->response->redirectByName(RouteName::ROLES);
    }
}
