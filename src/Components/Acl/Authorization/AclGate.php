<?php declare(strict_types=1);

namespace Concept\Components\Acl\Authorization;

use Concept\Components\Acl\Contracts\AclInterface;
use Concept\Components\Acl\Contracts\RoleResolverInterface;
use Laminas\Permissions\Acl\Acl;

final class AclGate implements AclInterface
{
    public function __construct(
        private readonly Acl $acl,
        private readonly RoleResolverInterface $roleResolver,
    ) {}

    public function isAllowed(?string $resource = null, ?string $privilege = null): bool
    {
        return $this->acl->isAllowed($this->roleResolver->resolve(), $resource, $privilege);
    }

    public function role(): string
    {
        return $this->roleResolver->resolve();
    }
}
