<?php declare(strict_types=1);

namespace Concept\Components\Acl\Contracts;

interface AclDefinitionSourceInterface
{
    /**
     * @return array<string, string|null> role name => parent role name
     */
    public function roles(): array;

    /**
     * @return list<string|array{name: string, parent?: string}>
     */
    public function resources(): array;

    /**
     * @return list<array{role?: string, resource?: string, privilege?: string}>
     */
    public function allowRules(): array;

    /**
     * @return list<array{role?: string, resource?: string, privilege?: string}>
     */
    public function denyRules(): array;

    public function invalidate(): void;
}
