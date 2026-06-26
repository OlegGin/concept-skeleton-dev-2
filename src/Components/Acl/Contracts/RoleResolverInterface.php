<?php declare(strict_types=1);

namespace Concept\Components\Acl\Contracts;

interface RoleResolverInterface
{
    public function resolve(): string;
}
