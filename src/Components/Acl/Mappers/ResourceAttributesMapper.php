<?php declare(strict_types=1);

namespace Concept\Components\Acl\Mappers;

use Concept\Common\Mappers\FormValueNormalizer;
use Concept\Components\Acl\Dto\Resource\ResourceDto;
use Concept\Components\Acl\Models\AclResourceModel;

final class ResourceAttributesMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toAttributes(ResourceDto $dto): array
    {
        return [
            AclResourceModel::FIELD_NAME => $dto->name,
            AclResourceModel::FIELD_PARENT_ID => FormValueNormalizer::nullableInt($dto->parent_id),
        ];
    }
}
