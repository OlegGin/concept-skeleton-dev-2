<?php declare(strict_types=1);

namespace Concept\Components\Acl\Database\Seeders;

use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Components\AuthAdmin\Models\UserModel;
use Concept\Core\Services\Database\Contracts\SeederInterface;
use Illuminate\Database\Seeder;

class AssignUserAclRolesSeeder extends Seeder implements SeederInterface
{
    public function run(): void
    {
        $roles = $this->roleIdsByName();
        if ($roles === []) {
            return;
        }

        $assignments = [
            'admin@example.com' => 'admin',
            'manager@example.com' => 'manager',
            'editor@example.com' => 'editor',
            'user@example.com' => 'user',
        ];

        foreach ($assignments as $email => $roleName) {
            $roleId = $roles[$roleName] ?? null;
            if ($roleId === null) {
                continue;
            }

            UserModel::query()
                ->where(UserModel::FIELD_EMAIL, $email)
                ->update([UserModel::FIELD_ACL_ROLE_ID => $roleId]);
        }
    }

    /**
     * @return array<string, int>
     */
    private function roleIdsByName(): array
    {
        $roles = AclRoleModel::query()
            ->get([AclRoleModel::FIELD_ID, AclRoleModel::FIELD_NAME]);

        $map = [];
        foreach ($roles as $role) {
            /** @var AclRoleModel $role */
            $map[$role->getName()] = (int) $role->getId();
        }

        return $map;
    }
}
