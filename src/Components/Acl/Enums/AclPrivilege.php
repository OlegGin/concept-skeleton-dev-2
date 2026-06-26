<?php declare(strict_types=1);

namespace Concept\Components\Acl\Enums;

enum AclPrivilege: string
{
    case View = 'view';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    /** @return non-empty-string */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', array_column(self::cases(), 'value'));
    }
}
