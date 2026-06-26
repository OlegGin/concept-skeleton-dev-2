<?php declare(strict_types=1);

namespace Concept\Components\Acl\Enums;

enum AclRouteZone: string
{
    case Admin = 'admin';
    case Cabinet = 'cabinet';
    case Default = 'default';
}
