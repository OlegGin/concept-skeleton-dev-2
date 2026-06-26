<?php declare(strict_types=1);

namespace Concept\Components\Acl\Mappers;

use Concept\Common\Mappers\FormValueNormalizer;
use Concept\Components\Acl\Dto\Rule\RuleDto;
use Concept\Components\Acl\Models\AclRuleModel;

final class RuleAttributesMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toAttributes(RuleDto $dto): array
    {
        return [
            AclRuleModel::FIELD_TYPE => $dto->type,
            AclRuleModel::FIELD_ROLE_ID => FormValueNormalizer::nullableInt($dto->role_id),
            AclRuleModel::FIELD_RESOURCE_ID => FormValueNormalizer::nullableInt($dto->resource_id),
            AclRuleModel::FIELD_PRIVILEGE => FormValueNormalizer::nullableString($dto->privilege),
        ];
    }
}
