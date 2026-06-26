<?php declare(strict_types=1);

namespace Concept\Components\Acl\Services;

use Concept\Components\Acl\Models\AclRouteRuleModel;

final class AclRouteRulesService
{
    /** @var array<string, array{resource: string, privilege?: string, redirect_route_name?: string}>|null */
    private ?array $map = null;

    public function __construct(
        private readonly AclRouteRuleModel $routeRuleModel,
        private readonly AclEntityLookup $lookup,
    ) {}

    /**
     * @return array{resource: string, privilege?: string, redirect_route_name?: string}|null
     */
    public function resolve(string $routeName): ?array
    {
        return $this->map()[$routeName] ?? null;
    }

    public function invalidate(): void
    {
        $this->map = null;
    }

    /**
     * @return array<string, array{resource: string, privilege?: string, redirect_route_name?: string}>
     */
    private function map(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $rules = $this->routeRuleModel
            ->newQuery()
            ->orderBy(AclRouteRuleModel::FIELD_ROUTE_NAME)
            ->get();

        /** @var array<string, array{resource: string, privilege?: string, redirect_route_name?: string}> $map */
        $map = [];

        foreach ($rules as $rule) {
            /** @var AclRouteRuleModel $rule */
            $resourceId = $rule->getAttribute(AclRouteRuleModel::FIELD_RESOURCE_ID);
            if (!is_numeric($resourceId)) {
                continue;
            }

            $resourceName = $this->lookup->resourceNameById((int) $resourceId);
            if ($resourceName === null) {
                continue;
            }

            $entry = ['resource' => $resourceName];

            $privilege = $rule->getPrivilege();
            if ($privilege !== null) {
                $entry['privilege'] = $privilege;
            }

            $redirectRouteName = $rule->getRedirectRouteName();
            if ($redirectRouteName !== null) {
                $entry['redirect_route_name'] = $redirectRouteName;
            }

            $map[$rule->getRouteName()] = $entry;
        }

        $this->map = $map;

        return $this->map;
    }
}
