<?php declare(strict_types=1);

namespace Concept\Components\Acl\Constants;

final class ViewName
{
    public const string MATRIX_INDEX = '@acl/admin/matrix/index';

    public const string ROLES_LIST = '@acl/admin/roles/list';
    public const string ROLES_CREATE = '@acl/admin/roles/create';
    public const string ROLES_EDIT = '@acl/admin/roles/edit';

    public const string RESOURCES_LIST = '@acl/admin/resources/list';
    public const string RESOURCES_CREATE = '@acl/admin/resources/create';
    public const string RESOURCES_EDIT = '@acl/admin/resources/edit';

    public const string RULES_LIST = '@acl/admin/rules/list';
    public const string RULES_CREATE = '@acl/admin/rules/create';
    public const string RULES_EDIT = '@acl/admin/rules/edit';

    public const string ROUTE_RULES_LIST = '@acl/admin/route-rules/list';
    public const string ROUTE_RULES_CREATE = '@acl/admin/route-rules/create';
    public const string ROUTE_RULES_EDIT = '@acl/admin/route-rules/edit';
}
