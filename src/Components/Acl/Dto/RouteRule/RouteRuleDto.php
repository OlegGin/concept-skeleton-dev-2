<?php declare(strict_types=1);

namespace Concept\Components\Acl\Dto\RouteRule;

use Concept\Core\Services\Dto\Contracts\DtoInterface;
use Concept\Core\Services\Dto\Dto;

final class RouteRuleDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $route_name,
        public readonly int $resource_id,
        public readonly ?string $privilege = null,
        public readonly ?string $redirect_route_name = null,
    ) {}
}
