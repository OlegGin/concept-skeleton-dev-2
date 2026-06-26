<?php declare(strict_types=1);

namespace Concept\Components\Acl\Dto\Role;

use Concept\Core\Services\Dto\Contracts\DtoInterface;
use Concept\Core\Services\Dto\Dto;

final class RoleDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $name,
        public readonly int|string|null $parent_id = null,
    ) {}
}
