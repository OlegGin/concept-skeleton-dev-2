<?php declare(strict_types=1);

namespace Concept\App\Http\Dto;

use Concept\Extensions\CastingValinor\Dto\Dto;

final class LoginDto extends Dto
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
