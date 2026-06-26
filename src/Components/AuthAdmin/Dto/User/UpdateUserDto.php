<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Dto\User;

use Concept\Extensions\CastingValinor\Contracts\DtoInterface;
use Concept\Extensions\CastingValinor\Dto\Dto;

class UpdateUserDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly string|bool|null $is_admin = null,
        public readonly int|string|null $acl_role_id = null,
        public readonly ?string $verification_token = null,
        public readonly ?string $password_reset_token = null,
        public readonly ?string $reset_token_expires_at = null,
    ) {}
}
