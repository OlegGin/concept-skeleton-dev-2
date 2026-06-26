<?php declare(strict_types=1);

namespace Concept\Components\Acl\RoleResolvers;

use Concept\Components\Acl\Contracts\RoleResolverInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;

final class DefaultRoleResolver implements RoleResolverInterface
{
    public function __construct(
        private readonly ConfigInterface $config,
    ) {}

    public function resolve(): string
    {
        return $this->config->getString('acl.default_role', 'guest');
    }
}
