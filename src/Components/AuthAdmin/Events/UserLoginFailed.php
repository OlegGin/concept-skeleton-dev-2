<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Events;

final readonly class UserLoginFailed
{
    public function __construct(
        public string $email,
    ) {}
}
