<?php declare(strict_types=1);

namespace Concept\Components\Acl\Services;

use Concept\Components\Acl\Authorization\AclBuilder;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Enums\AclRuleType;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Components\Acl\Models\AclRuleModel;
use Illuminate\Database\Eloquent\Builder;

final class AclMatrixService
{
    public function __construct(
        private readonly AclBuilder $aclBuilder,
        private readonly AclEntityLookup $lookup,
        private readonly AclRuleModel $ruleModel,
    ) {}

    /**
     * @return array{
     *     roles: list<AclRoleModel>,
     *     resources: list<AclResourceModel>,
     *     cells: array<int, array<int, array{allowed: bool, explicit: ?string}>>
     * }
     */
    public function build(?string $privilege = null): array
    {
        $privilege = $this->normalizePrivilege($privilege);
        $acl = $this->aclBuilder->build();

        $roles = $this->sortedRoles();
        $resources = $this->sortedResources();
        $directRules = $this->directRulesMap($privilege);

        /** @var array<int, array<int, array{allowed: bool, explicit: ?string}>> $cells */
        $cells = [];

        foreach ($roles as $role) {
            $roleId = $role->getId();

            foreach ($resources as $resource) {
                $resourceId = $resource->getId();

                $cells[$roleId][$resourceId] = [
                    'allowed' => $acl->isAllowed($role->getName(), $resource->getName(), $privilege),
                    'explicit' => $directRules[$roleId][$resourceId] ?? null,
                ];
            }
        }

        return [
            'roles' => $roles,
            'resources' => $resources,
            'cells' => $cells,
        ];
    }

    public function setAccess(int $roleId, int $resourceId, string $action, ?string $privilege = null): void
    {
        $privilege = $this->normalizePrivilege($privilege);
        $action = strtolower($action);

        if ($action === 'deny') {
            $this->preserveInheritedAccessForDescendants($roleId, $resourceId);
        }

        $this->deleteDirectRules($roleId, $resourceId, $privilege);

        if ($action === 'allow') {
            $this->createRule(AclRuleType::Allow, $roleId, $resourceId, $privilege);

            $this->aclBuilder->invalidate();

            return;
        }

        if ($action === 'deny') {
            $this->createRule(AclRuleType::Deny, $roleId, $resourceId, $privilege);
        }

        $this->aclBuilder->invalidate();
    }

    /**
     * @return list<AclRoleModel>
     */
    private function sortedRoles(): array
    {
        $roles = array_values($this->lookup->rolesById());

        usort(
            $roles,
            static fn (AclRoleModel $left, AclRoleModel $right): int => $left->getName() <=> $right->getName(),
        );

        return $roles;
    }

    /**
     * @return list<AclResourceModel>
     */
    private function sortedResources(): array
    {
        $resources = array_values($this->lookup->resourcesById());

        usort(
            $resources,
            static fn (AclResourceModel $left, AclResourceModel $right): int => $left->getName() <=> $right->getName(),
        );

        return $resources;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function directRulesMap(?string $privilege): array
    {
        $query = $this->ruleModel->newQuery();

        if ($privilege === null) {
            $query->whereNull(AclRuleModel::FIELD_PRIVILEGE);
        } else {
            $query->where(AclRuleModel::FIELD_PRIVILEGE, $privilege);
        }

        $rules = $query->get();

        /** @var array<int, array<int, string>> $map */
        $map = [];

        /** @var AclRuleModel $rule */
        foreach ($rules as $rule) {
            $roleId = $rule->getAttribute(AclRuleModel::FIELD_ROLE_ID);
            $resourceId = $rule->getAttribute(AclRuleModel::FIELD_RESOURCE_ID);

            if (!is_numeric($roleId) || !is_numeric($resourceId)) {
                continue;
            }

            $map[(int) $roleId][(int) $resourceId] = $rule->getType()->value;
        }

        return $map;
    }

    private function deleteDirectRules(int $roleId, int $resourceId, ?string $privilege): void
    {
        $this->directRuleQuery($roleId, $resourceId, $privilege)->delete();
    }

    private function createRule(AclRuleType $type, int $roleId, int $resourceId, ?string $privilege): void
    {
        $this->ruleModel->newQuery()->create([
            AclRuleModel::FIELD_TYPE => $type->value,
            AclRuleModel::FIELD_ROLE_ID => $roleId,
            AclRuleModel::FIELD_RESOURCE_ID => $resourceId,
            AclRuleModel::FIELD_PRIVILEGE => $privilege,
        ]);
    }

    /**
     * @return Builder<AclRuleModel>
     */
    private function directRuleQuery(int $roleId, int $resourceId, ?string $privilege): Builder
    {
        $query = $this->ruleModel
            ->newQuery()
            ->where(AclRuleModel::FIELD_ROLE_ID, $roleId)
            ->where(AclRuleModel::FIELD_RESOURCE_ID, $resourceId);

        if ($privilege === null) {
            $query->whereNull(AclRuleModel::FIELD_PRIVILEGE);
        } else {
            $query->where(AclRuleModel::FIELD_PRIVILEGE, $privilege);
        }

        return $query;
    }

    private function normalizePrivilege(?string $privilege): ?string
    {
        if ($privilege === null || $privilege === '') {
            return null;
        }

        return AclPrivilege::tryFrom($privilege)?->value;
    }

    /**
     * Laminas ACL walks parent roles when checking access. A privilege-specific DENY on a parent
     * would otherwise remove inherited access for child roles in the graded chain (e.g. manager → admin).
     */
    private function preserveInheritedAccessForDescendants(int $roleId, int $resourceId): void
    {
        $resource = $this->lookup->resourcesById()[$resourceId] ?? null;
        if ($resource === null) {
            return;
        }

        $acl = $this->aclBuilder->build();
        $resourceName = $resource->getName();

        foreach ($this->descendantRoleIds($roleId) as $descendantId) {
            $descendant = $this->lookup->rolesById()[$descendantId] ?? null;
            if ($descendant === null) {
                continue;
            }

            if (!$acl->isAllowed($descendant->getName(), $resourceName)) {
                continue;
            }

            if ($this->directRuleQuery($descendantId, $resourceId, null)->exists()) {
                continue;
            }

            $this->createRule(AclRuleType::Allow, $descendantId, $resourceId, null);
        }
    }

    /**
     * @return list<int>
     */
    private function descendantRoleIds(int $roleId): array
    {
        /** @var array<int, list<int>> $childrenByParentId */
        $childrenByParentId = [];

        foreach ($this->lookup->rolesById() as $role) {
            $parentId = $role->getAttribute(AclRoleModel::FIELD_PARENT_ID);
            if (!is_numeric($parentId)) {
                continue;
            }

            $childrenByParentId[(int) $parentId][] = $role->getId();
        }

        /** @var list<int> $descendantIds */
        $descendantIds = [];
        $stack = $childrenByParentId[$roleId] ?? [];

        while ($stack !== []) {
            $descendantId = array_pop($stack);
            $descendantIds[] = $descendantId;

            foreach ($childrenByParentId[$descendantId] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return $descendantIds;
    }
}
