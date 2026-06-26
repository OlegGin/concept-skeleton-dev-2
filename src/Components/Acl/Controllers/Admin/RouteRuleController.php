<?php declare(strict_types=1);

namespace Concept\Components\Acl\Controllers\Admin;

use Concept\Components\Acl\Constants\RouteName;
use Concept\Components\Acl\Constants\ViewName;
use Concept\Components\Acl\Dto\RouteRule\RouteRuleDto;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Mappers\RouteRuleAttributesMapper;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRouteRuleModel;
use Concept\Components\Acl\Requests\RouteRules\StoreRouteRuleRequest;
use Concept\Components\Acl\Requests\RouteRules\UpdateRouteRuleRequest;
use Concept\Components\Acl\Services\AclRouteRulesService;
use Concept\Components\Acl\Support\AclListQuery;
use Concept\Components\Acl\Support\NamedRouteCatalog;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteRuleController
{
    private const string CONFIG_PAGINATION_PER_PAGE = 'pagination.per_page';
    private const int DEFAULT_PAGINATION_PER_PAGE = 10;

    private const string MSG_ROUTE_RULE_CREATED = 'Route rule created successfully.';
    private const string MSG_ROUTE_RULE_UPDATED = 'Route rule updated successfully.';
    private const string MSG_ROUTE_RULE_DELETED = 'Route rule deleted successfully.';
    private const string MSG_ROUTE_RULE_NOT_FOUND = 'Route rule not found.';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly FlashBagInterface $flashBag,
        private readonly ConfigInterface $config,
        private readonly AclRouteRuleModel $routeRuleModel,
        private readonly AclResourceModel $resourceModel,
        private readonly AclRouteRulesService $routeRulesService,
        private readonly NamedRouteCatalog $namedRouteCatalog,
        private readonly RouteRuleAttributesMapper $routeRuleAttributesMapper,
        private readonly AclListQuery $listQuery,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $params */
        $params = $request->getQueryParams();

        $filters = $this->listQuery->routeRuleFilters($params);
        [$sortBy, $sortDirection] = $this->listQuery->routeRuleSort($params);
        $query = $this->listQuery->viewContext($filters, $sortBy, $sortDirection);

        $builder = $this->routeRuleModel->newQuery()->with('resource');

        $this->listQuery->applyRouteRuleFilters($builder, $filters);
        $this->listQuery->applyRouteRuleSort($builder, $sortBy, $sortDirection);

        $rules = $builder
            ->paginate($this->config->getInt(self::CONFIG_PAGINATION_PER_PAGE, self::DEFAULT_PAGINATION_PER_PAGE))
            ->appends($query);

        return $this->viewResponse->create(ViewName::ROUTE_RULES_LIST, [
            'rules' => $rules,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'query' => $query,
            'privileges' => AclPrivilege::cases(),
            'resources' => $this->resourceOptions(),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->viewResponse->create(ViewName::ROUTE_RULES_CREATE, [
            'privileges' => AclPrivilege::cases(),
            'resources' => $this->resourceOptions(),
            'route_tree' => $this->namedRouteCatalog->treeWithSelected(null),
        ]);
    }

    public function store(StoreRouteRuleRequest $request): ResponseInterface
    {
        /** @var RouteRuleDto $dto */
        $dto = $request->toDto();
        $this->routeRuleModel->newQuery()->create($this->routeRuleAttributesMapper->toAttributes($dto));
        $this->routeRulesService->invalidate();
        $this->flashBag->addSuccess(self::MSG_ROUTE_RULE_CREATED);

        return $this->response->redirectByName(RouteName::ROUTE_RULES);
    }

    public function edit(int $id): ResponseInterface
    {
        $rule = $this->findRule($id);
        if ($rule === null) {
            return $this->redirectNotFound();
        }

        return $this->viewResponse->create(ViewName::ROUTE_RULES_EDIT, [
            'rule' => $rule,
            'privileges' => AclPrivilege::cases(),
            'resources' => $this->resourceOptions(),
            'route_tree' => $this->namedRouteCatalog->treeWithSelected($rule->getRouteName()),
        ]);
    }

    public function update(UpdateRouteRuleRequest $request, int $id): ResponseInterface
    {
        $rule = $this->findRule($id);
        if ($rule === null) {
            return $this->redirectNotFound();
        }

        /** @var RouteRuleDto $dto */
        $dto = $request->toDto();
        $rule->update($this->routeRuleAttributesMapper->toAttributes($dto));
        $this->routeRulesService->invalidate();
        $this->flashBag->addSuccess(self::MSG_ROUTE_RULE_UPDATED);

        return $this->response->redirectByName(RouteName::ROUTE_RULES);
    }

    public function destroy(int $id): ResponseInterface
    {
        $rule = $this->findRule($id);
        if ($rule === null) {
            return $this->redirectNotFound();
        }

        $rule->delete();
        $this->routeRulesService->invalidate();
        $this->flashBag->addSuccess(self::MSG_ROUTE_RULE_DELETED);

        return $this->response->redirectByName(RouteName::ROUTE_RULES);
    }

    private function findRule(int $id): ?AclRouteRuleModel
    {
        $rule = $this->routeRuleModel->newQuery()->find($id);

        return $rule instanceof AclRouteRuleModel ? $rule : null;
    }

    /**
     * @return list<AclResourceModel>
     */
    private function resourceOptions(): array
    {
        /** @var list<AclResourceModel> */
        return $this->resourceModel
            ->newQuery()
            ->orderBy(AclResourceModel::FIELD_NAME)
            ->get()
            ->all();
    }

    private function redirectNotFound(): ResponseInterface
    {
        $this->flashBag->addError(self::MSG_ROUTE_RULE_NOT_FOUND);

        return $this->response->redirectByName(RouteName::ROUTE_RULES);
    }
}
