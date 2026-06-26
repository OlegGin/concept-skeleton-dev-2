<?php declare(strict_types=1);

namespace Concept\Components\Acl\Services;

use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRoleModel;

final class AclEntityLookup
{
    /** @var array<int, AclRoleModel>|null */
    private ?array $rolesById = null;

    /** @var array<int, AclResourceModel>|null */
    private ?array $resourcesById = null;

    public function __construct(
        private readonly AclRoleModel $roleModel,
        private readonly AclResourceModel $resourceModel,
    ) {}

    /**
     * @return array<int, AclRoleModel>
     */
    public function rolesById(): array
    {
        if ($this->rolesById !== null) {
            return $this->rolesById;
        }

        /** @var array<int, AclRoleModel> $map */
        $map = $this->roleModel
            ->newQuery()
            ->orderBy(AclRoleModel::FIELD_ID)
            ->get()
            ->keyBy(AclRoleModel::FIELD_ID)
            ->all();

        return $this->rolesById = $map;
    }

    /**
     * @return array<int, AclResourceModel>
     */
    public function resourcesById(): array
    {
        if ($this->resourcesById !== null) {
            return $this->resourcesById;
        }

        /** @var array<int, AclResourceModel> $map */
        $map = $this->resourceModel
            ->newQuery()
            ->orderBy(AclResourceModel::FIELD_ID)
            ->get()
            ->keyBy(AclResourceModel::FIELD_ID)
            ->all();

        return $this->resourcesById = $map;
    }

    public function roleNameById(int $id): ?string
    {
        if (!isset($this->rolesById()[$id])) {
            return null;
        }

        return $this->rolesById()[$id]->getName();
    }

    public function resourceNameById(int $id): ?string
    {
        if (!isset($this->resourcesById()[$id])) {
            return null;
        }

        return $this->resourcesById()[$id]->getName();
    }
}
