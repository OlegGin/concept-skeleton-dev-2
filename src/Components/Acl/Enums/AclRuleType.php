<?php declare(strict_types=1);

namespace Concept\Components\Acl\Enums;

enum AclRuleType: string
{
    case Allow = 'allow';
    case Deny = 'deny';
}
