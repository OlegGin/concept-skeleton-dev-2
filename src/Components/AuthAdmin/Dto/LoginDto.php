<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Dto;

use Concept\Core\Services\Dto\Contracts\DtoInterface;
use Concept\Core\Services\Dto\Dto;

class LoginDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $remember = false,
    ) {}
}