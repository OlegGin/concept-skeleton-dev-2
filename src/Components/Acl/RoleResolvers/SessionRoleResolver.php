<?php declare(strict_types=1);

namespace Concept\Components\Acl\RoleResolvers;

use Concept\Components\Acl\Contracts\RoleResolverInterface;
use Concept\Components\Acl\Services\AclEntityLookup;
use Concept\Components\AuthAdmin\Models\UserModel;
use Concept\Components\AuthAdmin\Services\AuthService;
use Concept\Extensions\Config\Contracts\ConfigInterface;

final class SessionRoleResolver implements RoleResolverInterface
{
    private string $resolvedRole = '';
    private bool $resolved = false;

    public function __construct(
        private readonly AuthService $auth,
        private readonly ConfigInterface $config,
        private readonly AclEntityLookup $lookup,
    ) {}

    public function resolve(): string
    {
        if (!$this->resolved) {
            $this->resolvedRole = $this->lookupRole();
            $this->resolved = true;
        }

        return $this->resolvedRole;
    }

    private function lookupRole(): string
    {
        $user = $this->auth->user();
        if (!($user instanceof UserModel)) {
            return $this->config->getString('acl.default_role', 'guest');
        }

        $roleId = $user->getAttribute(UserModel::FIELD_ACL_ROLE_ID);
        if (!is_numeric($roleId)) {
            return $this->config->getString('acl.default_user_role', 'user');
        }

        $roleName = $this->lookup->roleNameById((int) $roleId);

        return $roleName ?? $this->config->getString('acl.default_user_role', 'user');
    }
}
