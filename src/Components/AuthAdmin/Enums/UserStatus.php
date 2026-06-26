<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Enums;

enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
}
