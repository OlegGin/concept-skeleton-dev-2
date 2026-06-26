<?php declare(strict_types=1);

namespace Concept\Components\Acl\Support;

use Concept\Common\Models\BaseModel;
use Concept\Common\Support\ListQuery;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Enums\AclRuleType;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Components\Acl\Models\AclRouteRuleModel;
use Concept\Components\Acl\Models\AclRuleModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class AclListQuery
{
    public const string PARENT_ROOT = 'root';
    public const string SORT_PARENT_NAME = 'parent_name';
    public const string SORT_ROLE_NAME = 'role_name';
    public const string SORT_RESOURCE_NAME = 'resource_name';

    public function __construct(
        private readonly ListQuery $listQuery,
    ) {}

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, string>
     */
    public function roleFilters(array $params): array
    {
        $filters = $this->listQuery->filters($params, ['name', 'parent_id']);

        if (isset($filters['parent_id'])
            && $filters['parent_id'] !== self::PARENT_ROOT
            && !ctype_digit($filters['parent_id'])
        ) {
            unset($filters['parent_id']);
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{0: string, 1: string}
     */
    public function roleSort(array $params): array
    {
        return $this->listQuery->sort($params, [
            BaseModel::FIELD_ID,
            AclRoleModel::FIELD_NAME,
            self::SORT_PARENT_NAME,
            'children_count',
            'rules_count',
        ], AclRoleModel::FIELD_NAME);
    }

    /**
     * @param Builder<covariant Model> $query
     * @param array<string, string> $filters
     */
    public function applyRoleFilters(Builder $query, array $filters): void
    {
        $this->applyNameAndParentFilters(
            $query,
            $filters,
            AclRoleModel::FIELD_NAME,
            AclRoleModel::FIELD_PARENT_ID,
        );
    }

    /** @param Builder<covariant Model> $query */
    public function applyRoleSort(Builder $query, string $sortBy, string $sortDirection): void
    {
        $this->applyParentTreeSort($query, AclRoleModel::TABLE_NAME, 'parent_roles', $sortBy, $sortDirection);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, string>
     */
    public function resourceFilters(array $params): array
    {
        return $this->roleFilters($params);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{0: string, 1: string}
     */
    public function resourceSort(array $params): array
    {
        return $this->listQuery->sort($params, [
            BaseModel::FIELD_ID,
            AclResourceModel::FIELD_NAME,
            self::SORT_PARENT_NAME,
            'children_count',
            'rules_count',
        ], AclResourceModel::FIELD_NAME);
    }

    /**
     * @param Builder<covariant Model> $query
     * @param array<string, string> $filters
     */
    public function applyResourceFilters(Builder $query, array $filters): void
    {
        $this->applyNameAndParentFilters(
            $query,
            $filters,
            AclResourceModel::FIELD_NAME,
            AclResourceModel::FIELD_PARENT_ID,
        );
    }

    /** @param Builder<covariant Model> $query */
    public function applyResourceSort(Builder $query, string $sortBy, string $sortDirection): void
    {
        $this->applyParentTreeSort($query, AclResourceModel::TABLE_NAME, 'parent_resources', $sortBy, $sortDirection);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, string>
     */
    public function ruleFilters(array $params): array
    {
        $filters = $this->listQuery->filters($params, ['type', 'role_id', 'resource_id', 'privilege']);

        if (isset($filters['type']) && AclRuleType::tryFrom($filters['type']) === null) {
            unset($filters['type']);
        }

        if (isset($filters['privilege']) && AclPrivilege::tryFrom($filters['privilege']) === null) {
            unset($filters['privilege']);
        }

        if (isset($filters['role_id']) && !ctype_digit($filters['role_id'])) {
            unset($filters['role_id']);
        }

        if (isset($filters['resource_id']) && !ctype_digit($filters['resource_id'])) {
            unset($filters['resource_id']);
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{0: string, 1: string}
     */
    public function ruleSort(array $params): array
    {
        return $this->listQuery->sort($params, [
            BaseModel::FIELD_ID,
            AclRuleModel::FIELD_TYPE,
            self::SORT_ROLE_NAME,
            self::SORT_RESOURCE_NAME,
            AclRuleModel::FIELD_PRIVILEGE,
        ], BaseModel::FIELD_ID);
    }

    /**
     * @param Builder<covariant Model> $query
     * @param array<string, string> $filters
     */
    public function applyRuleFilters(Builder $query, array $filters): void
    {
        if (isset($filters['type'])) {
            $query->where(AclRuleModel::FIELD_TYPE, $filters['type']);
        }

        if (isset($filters['role_id'])) {
            $query->where(AclRuleModel::FIELD_ROLE_ID, $filters['role_id']);
        }

        if (isset($filters['resource_id'])) {
            $query->where(AclRuleModel::FIELD_RESOURCE_ID, $filters['resource_id']);
        }

        if (isset($filters['privilege'])) {
            $query->where(AclRuleModel::FIELD_PRIVILEGE, $filters['privilege']);
        }
    }

    /** @param Builder<covariant Model> $query */
    public function applyRuleSort(Builder $query, string $sortBy, string $sortDirection): void
    {
        $table = AclRuleModel::TABLE_NAME;
        $direction = $this->listQuery->direction($sortDirection);

        if ($sortBy === self::SORT_ROLE_NAME) {
            $query
                ->leftJoin('acl_roles', sprintf('%s.role_id', $table), '=', 'acl_roles.id')
                ->orderBy('acl_roles.name', $direction)
                ->select(sprintf('%s.*', $table));

            return;
        }

        if ($sortBy === self::SORT_RESOURCE_NAME) {
            $query
                ->leftJoin('acl_resources', sprintf('%s.resource_id', $table), '=', 'acl_resources.id')
                ->orderBy('acl_resources.name', $direction)
                ->select(sprintf('%s.*', $table));

            return;
        }

        $query->orderBy($sortBy, $direction);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, string>
     */
    public function routeRuleFilters(array $params): array
    {
        $filters = $this->listQuery->filters($params, ['route_name', 'resource_id', 'privilege']);

        if (isset($filters['privilege']) && AclPrivilege::tryFrom($filters['privilege']) === null) {
            unset($filters['privilege']);
        }

        if (isset($filters['resource_id']) && !ctype_digit($filters['resource_id'])) {
            unset($filters['resource_id']);
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{0: string, 1: string}
     */
    public function routeRuleSort(array $params): array
    {
        return $this->listQuery->sort($params, [
            BaseModel::FIELD_ID,
            AclRouteRuleModel::FIELD_ROUTE_NAME,
            self::SORT_RESOURCE_NAME,
            AclRouteRuleModel::FIELD_PRIVILEGE,
            AclRouteRuleModel::FIELD_REDIRECT_ROUTE_NAME,
        ], AclRouteRuleModel::FIELD_ROUTE_NAME);
    }

    /**
     * @param Builder<covariant Model> $query
     * @param array<string, string> $filters
     */
    public function applyRouteRuleFilters(Builder $query, array $filters): void
    {
        if (isset($filters['route_name'])) {
            $query->where(
                AclRouteRuleModel::FIELD_ROUTE_NAME,
                'like',
                '%' . $this->listQuery->escapeLike($filters['route_name']) . '%',
            );
        }

        if (isset($filters['resource_id'])) {
            $query->where(AclRouteRuleModel::FIELD_RESOURCE_ID, $filters['resource_id']);
        }

        if (isset($filters['privilege'])) {
            $query->where(AclRouteRuleModel::FIELD_PRIVILEGE, $filters['privilege']);
        }
    }

    /** @param Builder<covariant Model> $query */
    public function applyRouteRuleSort(Builder $query, string $sortBy, string $sortDirection): void
    {
        $table = AclRouteRuleModel::TABLE_NAME;
        $direction = $this->listQuery->direction($sortDirection);

        if ($sortBy === self::SORT_RESOURCE_NAME) {
            $query
                ->leftJoin('acl_resources', sprintf('%s.resource_id', $table), '=', 'acl_resources.id')
                ->orderBy('acl_resources.name', $direction)
                ->select(sprintf('%s.*', $table));

            return;
        }

        $query->orderBy($sortBy, $direction);
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array<string, string>
     */
    public function viewContext(array $filters, string $sortBy, string $sortDirection): array
    {
        return $this->listQuery->context($filters, $sortBy, $sortDirection);
    }

    /**
     * @param Builder<covariant Model> $query
     * @param array<string, string> $filters
     */
    private function applyNameAndParentFilters(
        Builder $query,
        array $filters,
        string $nameField,
        string $parentIdField,
    ): void {
        if (isset($filters['name'])) {
            $query->where(
                $nameField,
                'like',
                '%' . $this->listQuery->escapeLike($filters['name']) . '%',
            );
        }

        if (!isset($filters['parent_id'])) {
            return;
        }

        if ($filters['parent_id'] === self::PARENT_ROOT) {
            $query->whereNull($parentIdField);

            return;
        }

        $query->where($parentIdField, $filters['parent_id']);
    }

    /** @param Builder<covariant Model> $query */
    private function applyParentTreeSort(
        Builder $query,
        string $table,
        string $parentAlias,
        string $sortBy,
        string $sortDirection,
    ): void {
        $direction = $this->listQuery->direction($sortDirection);

        if ($sortBy === self::SORT_PARENT_NAME) {
            $query
                ->leftJoin(
                    sprintf('%s as %s', $table, $parentAlias),
                    sprintf('%s.parent_id', $table),
                    '=',
                    sprintf('%s.id', $parentAlias),
                )
                ->orderBy(sprintf('%s.name', $parentAlias), $direction)
                ->select(sprintf('%s.*', $table));

            return;
        }

        $query->orderBy($sortBy, $direction);
    }
}
