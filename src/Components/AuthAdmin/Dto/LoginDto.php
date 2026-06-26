<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Dto;

use Concept\Extensions\CastingValinor\Contracts\DtoInterface;
use Concept\Extensions\CastingValinor\Dto\Dto;

class LoginDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $remember = false,
    ) {}
}