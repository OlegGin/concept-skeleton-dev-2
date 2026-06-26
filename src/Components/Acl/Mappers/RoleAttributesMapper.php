<?php declare(strict_types=1);

namespace Concept\Components\Acl\Mappers;

use Concept\Common\Mappers\FormValueNormalizer;
use Concept\Components\Acl\Dto\Role\RoleDto;
use Concept\Components\Acl\Models\AclRoleModel;

final class RoleAttributesMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toAttributes(RoleDto $dto): array
    {
        return [
            AclRoleModel::FIELD_NAME => $dto->name,
            AclRoleModel::FIELD_PARENT_ID => FormValueNormalizer::nullableInt($dto->parent_id),
        ];
    }
}
