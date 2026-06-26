<?php declare(strict_types=1);

namespace Concept\Components\Acl\Mappers;

use Concept\Common\Mappers\FormValueNormalizer;
use Concept\Components\Acl\Dto\RouteRule\RouteRuleDto;
use Concept\Components\Acl\Models\AclRouteRuleModel;

final class RouteRuleAttributesMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toAttributes(RouteRuleDto $dto): array
    {
        return [
            AclRouteRuleModel::FIELD_ROUTE_NAME => $dto->route_name,
            AclRouteRuleModel::FIELD_RESOURCE_ID => $dto->resource_id,
            AclRouteRuleModel::FIELD_PRIVILEGE => FormValueNormalizer::nullableString($dto->privilege),
            AclRouteRuleModel::FIELD_REDIRECT_ROUTE_NAME => FormValueNormalizer::nullableString($dto->redirect_route_name),
        ];
    }
}
