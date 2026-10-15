<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Events;

final readonly class UserCreated
{
    public function __construct(
        public int $userId,
        public string $email,
        public ?int $actorUserId,
    ) {}
}
