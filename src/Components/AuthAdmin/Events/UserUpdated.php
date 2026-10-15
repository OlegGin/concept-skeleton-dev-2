<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Events;

final readonly class UserUpdated
{
    public function __construct(
        public int $userId,
        public string $email,
        public ?int $actorUserId,
    ) {}
}
