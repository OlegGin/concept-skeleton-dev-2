<?php declare(strict_types=1);

namespace Concept\Components\Acl\Database\Seeders;

use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRouteRuleModel;
use Concept\Extensions\DatabaseEloquent\Contracts\SeederInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Seeder;

class AclRouteRulesSeeder extends Seeder implements SeederInterface
{
    public function run(): void
    {
        $schema = CapsuleManager::schema();
        $connection = $schema->getConnection();

        $schema->disableForeignKeyConstraints();

        try {
            $connection->table('acl_route_rules')->truncate();
        } finally {
            $schema->enableForeignKeyConstraints();
        }

        $resources = $this->resourceIdsByName();
        if ($resources === []) {
            return;
        }

        $view = AclPrivilege::View->value;
        $create = AclPrivilege::Create->value;
        $update = AclPrivilege::Update->value;
        $delete = AclPrivilege::Delete->value;

        $rules = [
            ['route' => 'admin.home', 'resource' => 'admin'],
            ['route' => 'admin.dashboard', 'resource' => 'admin'],
            ['route' => 'admin.logout', 'resource' => 'admin'],

            ['route' => 'admin.users', 'resource' => 'admin.users', 'privilege' => $view],
            ['route' => 'admin.user.show', 'resource' => 'admin.users', 'privilege' => $view],
            ['route' => 'admin.user.create', 'resource' => 'admin.users', 'privilege' => $create],
            ['route' => 'admin.user.store', 'resource' => 'admin.users', 'privilege' => $create],
            ['route' => 'admin.user.edit', 'resource' => 'admin.users', 'privilege' => $update],
            ['route' => 'admin.user.update', 'resource' => 'admin.users', 'privilege' => $update],
            ['route' => 'admin.user.password', 'resource' => 'admin.users', 'privilege' => $update],
            ['route' => 'admin.user.destroy', 'resource' => 'admin.users', 'privilege' => $delete],
            ['route' => 'admin.users.generate-token-api', 'resource' => 'admin.users', 'privilege' => $update],

            ['route' => 'admin.settings', 'resource' => 'admin.settings', 'privilege' => $view],
            ['route' => 'admin.settings.create', 'resource' => 'admin.settings', 'privilege' => $create],
            ['route' => 'admin.settings.store', 'resource' => 'admin.settings', 'privilege' => $create],
            ['route' => 'admin.settings.edit', 'resource' => 'admin.settings', 'privilege' => $update],
            ['route' => 'admin.settings.update', 'resource' => 'admin.settings', 'privilege' => $update],
            ['route' => 'admin.settings.destroy', 'resource' => 'admin.settings', 'privilege' => $delete],

            ['route' => 'admin.acl.matrix', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.matrix.update', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.roles', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.role.create', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.role.store', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.role.edit', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.role.update', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.role.destroy', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.resources', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.resource.create', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.resource.store', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.resource.edit', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.resource.update', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.resource.destroy', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.rules', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.rule.create', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.rule.store', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.rule.edit', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.rule.update', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.rule.destroy', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.route-rules', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.route-rule.create', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.route-rule.store', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.route-rule.edit', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.route-rule.update', 'resource' => 'admin.acl'],
            ['route' => 'admin.acl.route-rule.destroy', 'resource' => 'admin.acl'],

            ['route' => 'cabinet.home', 'resource' => 'cabinet'],
            ['route' => 'cabinet.logout', 'resource' => 'cabinet'],
            ['route' => 'cabinet.profile', 'resource' => 'cabinet'],
            ['route' => 'profile.update', 'resource' => 'cabinet'],
            ['route' => 'profile.password.submit', 'resource' => 'cabinet'],
        ];

        foreach ($rules as $rule) {
            $resourceId = $resources[$rule['resource']] ?? null;
            if ($resourceId === null) {
                continue;
            }

            $model = new AclRouteRuleModel();
            $model->fill([
                AclRouteRuleModel::FIELD_ROUTE_NAME => $rule['route'],
                AclRouteRuleModel::FIELD_RESOURCE_ID => $resourceId,
                AclRouteRuleModel::FIELD_PRIVILEGE => $rule['privilege'] ?? null,
            ]);
            $model->save();
        }
    }

    /**
     * @return array<string, int>
     */
    private function resourceIdsByName(): array
    {
        $resources = AclResourceModel::query()
            ->get([AclResourceModel::FIELD_ID, AclResourceModel::FIELD_NAME]);

        $map = [];
        foreach ($resources as $resource) {
            /** @var AclResourceModel $resource */
            $map[$resource->getName()] = (int) $resource->getId();
        }

        return $map;
    }
}
