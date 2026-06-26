<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Dto\User;

use Concept\Extensions\CastingValinor\Contracts\DtoInterface;
use Concept\Extensions\CastingValinor\Dto\Dto;

class UpdateUserPasswordDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $password,
    ) {}
}
