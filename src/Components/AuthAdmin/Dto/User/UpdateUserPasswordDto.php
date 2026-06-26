<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Dto\User;

use Concept\Core\Services\Dto\Contracts\DtoInterface;
use Concept\Core\Services\Dto\Dto;

class UpdateUserPasswordDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $password,
    ) {}
}
