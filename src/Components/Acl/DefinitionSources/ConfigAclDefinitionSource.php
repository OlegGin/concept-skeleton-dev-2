<?php declare(strict_types=1);

namespace Concept\Components\Acl\DefinitionSources;

use Concept\Components\Acl\Contracts\AclDefinitionSourceInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;

final class ConfigAclDefinitionSource implements AclDefinitionSourceInterface
{
    public function __construct(
        private readonly ConfigInterface $config,
    ) {}

    public function roles(): array
    {
        /** @var array<string, mixed> $roles */
        $roles = $this->config->get('acl.definitions.roles', []);

        /** @var array<string, string|null> $result */
        $result = [];

        foreach ($roles as $role => $parent) {
            if ($role === '') {
                continue;
            }

            $result[$role] = is_string($parent) && $parent !== '' ? $parent : null;
        }

        return $result;
    }

    public function resources(): array
    {
        /** @var list<mixed> $resources */
        $resources = $this->config->get('acl.definitions.resources', []);

        /** @var list<string|array{name: string, parent?: string}> $result */
        $result = [];

        foreach ($resources as $resource) {
            if (is_string($resource) && $resource !== '') {
                $result[] = $resource;
                continue;
            }

            if (!is_array($resource)) {
                continue;
            }

            $name = $resource['name'] ?? null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $parent = $resource['parent'] ?? null;
            if (is_string($parent) && $parent !== '') {
                $result[] = ['name' => $name, 'parent' => $parent];
                continue;
            }

            $result[] = $name;
        }

        return $result;
    }

    public function allowRules(): array
    {
        return $this->rules('acl.definitions.allow');
    }

    public function denyRules(): array
    {
        return $this->rules('acl.definitions.deny');
    }

    /**
     * @return list<array{role?: string, resource?: string, privilege?: string}>
     */
    private function rules(string $configKey): array
    {
        /** @var list<mixed> $rules */
        $rules = $this->config->get($configKey, []);

        /** @var list<array{role?: string, resource?: string, privilege?: string}> $result */
        $result = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $entry = [];

            $role = $rule['role'] ?? null;
            if (is_string($role) && $role !== '') {
                $entry['role'] = $role;
            }

            $resource = $rule['resource'] ?? null;
            if (is_string($resource) && $resource !== '') {
                $entry['resource'] = $resource;
            }

            $privilege = $rule['privilege'] ?? null;
            if (is_string($privilege) && $privilege !== '') {
                $entry['privilege'] = $privilege;
            }

            $result[] = $entry;
        }

        return $result;
    }

    public function invalidate(): void
    {
    }
}
