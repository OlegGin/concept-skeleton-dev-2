<?php declare(strict_types=1);

namespace Concept\Components\Acl\Contracts;

interface AclInterface
{
    public function isAllowed(?string $resource = null, ?string $privilege = null): bool;

    public function role(): string;
}
