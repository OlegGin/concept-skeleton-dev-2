<?php declare(strict_types=1);

namespace Concept\Components\Acl\Dto\Resource;

use Concept\Extensions\CastingValinor\Contracts\DtoInterface;
use Concept\Extensions\CastingValinor\Dto\Dto;

final class ResourceDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $name,
        public readonly int|string|null $parent_id = null,
    ) {}
}
