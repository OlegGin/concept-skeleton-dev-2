<?php declare(strict_types=1);

namespace Concept\Components\Acl\Authorization;

use Concept\Components\Acl\Contracts\AclDefinitionSourceInterface;
use Concept\Components\Acl\Enums\AclRuleType;
use Laminas\Permissions\Acl\Acl;

final class AclBuilder
{
    private ?Acl $acl = null;

    public function __construct(
        private readonly AclDefinitionSourceInterface $definitionSource,
    ) {}

    public function build(): Acl
    {
        if ($this->acl !== null) {
            return $this->acl;
        }

        $acl = new Acl();

        $this->registerRoles($acl, $this->definitionSource->roles());
        $this->registerResources($acl, $this->definitionSource->resources());
        $this->registerRules($acl, $this->definitionSource->allowRules(), AclRuleType::Allow);
        $this->registerRules($acl, $this->definitionSource->denyRules(), AclRuleType::Deny);

        return $this->acl = $acl;
    }

    public function invalidate(): void
    {
        $this->acl = null;
        $this->definitionSource->invalidate();
    }

    /**
     * @param array<string, string|null> $roles
     */
    private function registerRoles(Acl $acl, array $roles): void
    {
        foreach ($roles as $role => $parent) {
            if ($role === '') {
                continue;
            }

            if (is_string($parent) && $parent !== '') {
                $acl->addRole($role, $parent);
                continue;
            }

            $acl->addRole($role);
        }
    }

    /**
     * @param list<string|array{name: string, parent?: string}> $resources
     */
    private function registerResources(Acl $acl, array $resources): void
    {
        foreach ($resources as $resource) {
            if (is_string($resource) && $resource !== '') {
                $acl->addResource($resource);
                continue;
            }

            if (!is_array($resource)) {
                continue;
            }

            $name = $resource['name'];
            if ($name === '') {
                continue;
            }

            $parent = $resource['parent'] ?? null;
            if (is_string($parent) && $parent !== '') {
                $acl->addResource($name, $parent);
                continue;
            }

            $acl->addResource($name);
        }
    }

    /**
     * @param list<array{role?: string, resource?: string, privilege?: string}> $rules
     */
    private function registerRules(Acl $acl, array $rules, AclRuleType $type): void
    {
        foreach ($rules as $rule) {
            $role = $rule['role'] ?? null;
            $resource = $rule['resource'] ?? null;
            $privilege = $rule['privilege'] ?? null;

            $role = is_string($role) && $role !== '' ? $role : null;
            $resource = is_string($resource) && $resource !== '' ? $resource : null;
            $privilege = is_string($privilege) && $privilege !== '' ? $privilege : null;

            match ($type) {
                AclRuleType::Allow => $acl->allow($role, $resource, $privilege),
                AclRuleType::Deny => $acl->deny($role, $resource, $privilege),
            };
        }
    }
}
