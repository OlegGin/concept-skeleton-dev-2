<?php declare(strict_types=1);

namespace Concept\Components\Acl\Dto\Rule;

use Concept\Extensions\CastingValinor\Contracts\DtoInterface;
use Concept\Extensions\CastingValinor\Dto\Dto;

final class RuleDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $type,
        public readonly int|string|null $role_id = null,
        public readonly int|string|null $resource_id = null,
        public readonly ?string $privilege = null,
    ) {}
}
