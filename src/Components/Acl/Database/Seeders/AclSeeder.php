<?php declare(strict_types=1);

namespace Concept\Components\Acl\Database\Seeders;

use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Enums\AclRuleType;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Components\Acl\Models\AclRuleModel;
use Concept\Extensions\DatabaseEloquent\Contracts\SeederInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Seeder;

class AclSeeder extends Seeder implements SeederInterface
{
    public function run(): void
    {
        $schema = CapsuleManager::schema();
        $connection = $schema->getConnection();

        $schema->disableForeignKeyConstraints();

        try {
            $connection->table('acl_route_rules')->truncate();
            $connection->table('acl_rules')->truncate();
            $connection->table('acl_resources')->truncate();
            $connection->table('acl_roles')->truncate();
        } finally {
            $schema->enableForeignKeyConstraints();
        }

        $guestId = $this->insertRole('guest');
        $userId = $this->insertRole('user', $guestId);
        $editorId = $this->insertRole('editor', $userId);
        $managerId = $this->insertRole('manager', $editorId);
        $adminId = $this->insertRole('admin');

        $cabinetId = $this->insertResource('cabinet');
        $adminResourceId = $this->insertResource('admin');
        $adminAclId = $this->insertResource('admin.acl', $adminResourceId);
        $adminUsersId = $this->insertResource('admin.users', $adminResourceId);
        $adminSettingsId = $this->insertResource('admin.settings', $adminResourceId);
        $adminContentId = $this->insertResource('admin.content', $adminResourceId);

        $this->insertRule(AclRuleType::Allow, $guestId, $cabinetId, AclPrivilege::View->value);
        $this->insertRule(AclRuleType::Deny, $guestId, $adminResourceId);

        $this->insertRule(AclRuleType::Allow, $userId, $cabinetId);
        $this->insertRule(AclRuleType::Deny, $userId, $adminResourceId);

        $this->insertRule(AclRuleType::Allow, $editorId, $adminResourceId);
        $this->insertRule(AclRuleType::Allow, $editorId, $adminContentId);
        $this->insertRule(AclRuleType::Deny, $editorId, $adminUsersId);
        $this->insertRule(AclRuleType::Deny, $editorId, $adminSettingsId);
        $this->insertRule(AclRuleType::Deny, $editorId, $adminAclId);

        $this->insertRule(AclRuleType::Allow, $managerId, $adminResourceId);
        $this->insertRule(AclRuleType::Allow, $managerId, $adminSettingsId);
        $this->insertRule(AclRuleType::Deny, $managerId, $adminAclId);
        $this->insertRule(AclRuleType::Allow, $managerId, $adminContentId);
        $this->insertRule(AclRuleType::Allow, $managerId, $adminUsersId);

        $this->insertRule(AclRuleType::Allow, $adminId, $adminResourceId);
    }

    private function insertRole(string $name, ?int $parentId = null): int
    {
        $model = new AclRoleModel();
        $model->fill([
            AclRoleModel::FIELD_NAME => $name,
            AclRoleModel::FIELD_PARENT_ID => $parentId,
        ]);
        $model->save();

        return (int) $model->getId();
    }

    private function insertResource(string $name, ?int $parentId = null): int
    {
        $model = new AclResourceModel();
        $model->fill([
            AclResourceModel::FIELD_NAME => $name,
            AclResourceModel::FIELD_PARENT_ID => $parentId,
        ]);
        $model->save();

        return (int) $model->getId();
    }

    private function insertRule(
        AclRuleType $type,
        ?int $roleId,
        ?int $resourceId,
        ?string $privilege = null,
    ): void {
        $model = new AclRuleModel();
        $model->fill([
            AclRuleModel::FIELD_TYPE => $type->value,
            AclRuleModel::FIELD_ROLE_ID => $roleId,
            AclRuleModel::FIELD_RESOURCE_ID => $resourceId,
            AclRuleModel::FIELD_PRIVILEGE => $privilege,
        ]);
        $model->save();
    }
}
