<?php declare(strict_types=1);

namespace Concept\Components\Acl\Constants;

final class RouteName
{
    public const string MATRIX = 'admin.acl.matrix';
    public const string MATRIX_UPDATE = 'admin.acl.matrix.update';

    public const string ROLES = 'admin.acl.roles';
    public const string ROLE_CREATE = 'admin.acl.role.create';
    public const string ROLE_STORE = 'admin.acl.role.store';
    public const string ROLE_EDIT = 'admin.acl.role.edit';
    public const string ROLE_UPDATE = 'admin.acl.role.update';
    public const string ROLE_DESTROY = 'admin.acl.role.destroy';

    public const string RESOURCES = 'admin.acl.resources';
    public const string RESOURCE_CREATE = 'admin.acl.resource.create';
    public const string RESOURCE_STORE = 'admin.acl.resource.store';
    public const string RESOURCE_EDIT = 'admin.acl.resource.edit';
    public const string RESOURCE_UPDATE = 'admin.acl.resource.update';
    public const string RESOURCE_DESTROY = 'admin.acl.resource.destroy';

    public const string RULES = 'admin.acl.rules';
    public const string RULE_CREATE = 'admin.acl.rule.create';
    public const string RULE_STORE = 'admin.acl.rule.store';
    public const string RULE_EDIT = 'admin.acl.rule.edit';
    public const string RULE_UPDATE = 'admin.acl.rule.update';
    public const string RULE_DESTROY = 'admin.acl.rule.destroy';

    public const string ROUTE_RULES = 'admin.acl.route-rules';
    public const string ROUTE_RULE_CREATE = 'admin.acl.route-rule.create';
    public const string ROUTE_RULE_STORE = 'admin.acl.route-rule.store';
    public const string ROUTE_RULE_EDIT = 'admin.acl.route-rule.edit';
    public const string ROUTE_RULE_UPDATE = 'admin.acl.route-rule.update';
    public const string ROUTE_RULE_DESTROY = 'admin.acl.route-rule.destroy';
}
