<?php declare(strict_types=1);

namespace Concept\Components\Acl\DefinitionSources;

use Concept\Components\Acl\Contracts\AclDefinitionSourceInterface;
use Concept\Components\Acl\Enums\AclRuleType;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Components\Acl\Models\AclRuleModel;
use Concept\Components\Acl\Services\AclEntityLookup;

final class DatabaseAclDefinitionSource implements AclDefinitionSourceInterface
{
    /** @var array<string, string|null>|null */
    private ?array $roles = null;

    /** @var list<string|array{name: string, parent?: string}>|null */
    private ?array $resources = null;

    /** @var array<string, list<array{role?: string, resource?: string, privilege?: string}>>|null */
    private ?array $rulesByType = null;

    public function __construct(
        private readonly AclEntityLookup $lookup,
        private readonly AclRuleModel $ruleModel,
    ) {}

    public function roles(): array
    {
        if ($this->roles !== null) {
            return $this->roles;
        }

        $rolesById = $this->lookup->rolesById();

        /** @var array<string, string|null> $result */
        $result = [];

        foreach ($rolesById as $role) {
            $result[$role->getName()] = $this->parentRoleName($role, $rolesById);
        }

        return $this->roles = $result;
    }

    public function resources(): array
    {
        if ($this->resources !== null) {
            return $this->resources;
        }

        $resourcesById = $this->lookup->resourcesById();

        /** @var list<string|array{name: string, parent?: string}> $result */
        $result = [];

        foreach ($resourcesById as $resource) {
            $parentName = $this->parentResourceName($resource, $resourcesById);
            if ($parentName !== null) {
                $result[] = ['name' => $resource->getName(), 'parent' => $parentName];
                continue;
            }

            $result[] = $resource->getName();
        }

        return $this->resources = $result;
    }

    public function allowRules(): array
    {
        return $this->rulesForType(AclRuleType::Allow);
    }

    public function denyRules(): array
    {
        return $this->rulesForType(AclRuleType::Deny);
    }

    /**
     * @return list<array{role?: string, resource?: string, privilege?: string}>
     */
    private function rulesForType(AclRuleType $type): array
    {
        return $this->rulesGroupedByType()[$type->value] ?? [];
    }

    /**
     * @return array<string, list<array{role?: string, resource?: string, privilege?: string}>>
     */
    private function rulesGroupedByType(): array
    {
        if ($this->rulesByType !== null) {
            return $this->rulesByType;
        }

        $rolesById = $this->lookup->rolesById();
        $resourcesById = $this->lookup->resourcesById();

        $rules = $this->ruleModel
            ->newQuery()
            ->orderBy(AclRuleModel::FIELD_ID)
            ->get();

        /** @var array<string, list<array{role?: string, resource?: string, privilege?: string}>> $grouped */
        $grouped = [
            AclRuleType::Allow->value => [],
            AclRuleType::Deny->value => [],
        ];

        /** @var AclRuleModel $rule */
        foreach ($rules as $rule) {
            $entry = [];

            $roleName = $this->relatedRoleName($rule, $rolesById);
            if ($roleName !== null) {
                $entry['role'] = $roleName;
            }

            $resourceName = $this->relatedResourceName($rule, $resourcesById);
            if ($resourceName !== null) {
                $entry['resource'] = $resourceName;
            }

            $privilege = $rule->getPrivilege();
            if ($privilege !== null) {
                $entry['privilege'] = $privilege;
            }

            $grouped[$rule->getType()->value][] = $entry;
        }

        return $this->rulesByType = $grouped;
    }

    /**
     * @param array<int, AclRoleModel> $rolesById
     */
    private function parentRoleName(AclRoleModel $role, array $rolesById): ?string
    {
        $parentId = $role->getAttribute(AclRoleModel::FIELD_PARENT_ID);
        if (!is_numeric($parentId)) {
            return null;
        }

        if (!isset($rolesById[(int) $parentId])) {
            return null;
        }

        return $rolesById[(int) $parentId]->getName();
    }

    /**
     * @param array<int, AclResourceModel> $resourcesById
     */
    private function parentResourceName(AclResourceModel $resource, array $resourcesById): ?string
    {
        $parentId = $resource->getAttribute(AclResourceModel::FIELD_PARENT_ID);
        if (!is_numeric($parentId)) {
            return null;
        }

        if (!isset($resourcesById[(int) $parentId])) {
            return null;
        }

        return $resourcesById[(int) $parentId]->getName();
    }

    /**
     * @param array<int, AclRoleModel> $rolesById
     */
    private function relatedRoleName(AclRuleModel $rule, array $rolesById): ?string
    {
        $roleId = $rule->getAttribute(AclRuleModel::FIELD_ROLE_ID);
        if (!is_numeric($roleId)) {
            return null;
        }

        if (!isset($rolesById[(int) $roleId])) {
            return null;
        }

        return $rolesById[(int) $roleId]->getName();
    }

    /**
     * @param array<int, AclResourceModel> $resourcesById
     */
    private function relatedResourceName(AclRuleModel $rule, array $resourcesById): ?string
    {
        $resourceId = $rule->getAttribute(AclRuleModel::FIELD_RESOURCE_ID);
        if (!is_numeric($resourceId)) {
            return null;
        }

        if (!isset($resourcesById[(int) $resourceId])) {
            return null;
        }

        return $resourcesById[(int) $resourceId]->getName();
    }

    public function invalidate(): void
    {
        $this->roles = null;
        $this->resources = null;
        $this->rulesByType = null;
    }
}
