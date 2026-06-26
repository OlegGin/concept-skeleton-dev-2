<?php declare(strict_types=1);

namespace Concept\Components\Acl\Controllers\Admin;

use Concept\Components\Acl\Constants\RouteName;
use Concept\Components\Acl\Constants\ViewName;
use Concept\Components\Acl\Dto\Rule\RuleDto;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Enums\AclRuleType;
use Concept\Components\Acl\Mappers\RuleAttributesMapper;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Models\AclRoleModel;
use Concept\Components\Acl\Models\AclRuleModel;
use Concept\Components\Acl\Requests\Rules\StoreRuleRequest;
use Concept\Components\Acl\Requests\Rules\UpdateRuleRequest;
use Concept\Components\Acl\Support\AclListQuery;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RuleController
{
    private const string CONFIG_PAGINATION_PER_PAGE = 'pagination.per_page';
    private const int DEFAULT_PAGINATION_PER_PAGE = 10;

    private const string MSG_RULE_CREATED = 'Rule created successfully.';
    private const string MSG_RULE_NOT_FOUND = 'Rule not found.';
    private const string MSG_RULE_UPDATED = 'Rule updated successfully.';
    private const string MSG_RULE_DELETED = 'Rule deleted successfully.';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly FlashBagInterface $flashBag,
        private readonly ConfigInterface $config,
        private readonly AclRuleModel $ruleModel,
        private readonly AclRoleModel $roleModel,
        private readonly AclResourceModel $resourceModel,
        private readonly RuleAttributesMapper $ruleAttributesMapper,
        private readonly AclListQuery $listQuery,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $params */
        $params = $request->getQueryParams();

        $filters = $this->listQuery->ruleFilters($params);
        [$sortBy, $sortDirection] = $this->listQuery->ruleSort($params);
        $query = $this->listQuery->viewContext($filters, $sortBy, $sortDirection);

        $builder = $this->ruleModel->newQuery()->with(['role', 'resource']);

        $this->listQuery->applyRuleFilters($builder, $filters);
        $this->listQuery->applyRuleSort($builder, $sortBy, $sortDirection);

        $rules = $builder
            ->paginate($this->config->getInt(self::CONFIG_PAGINATION_PER_PAGE, self::DEFAULT_PAGINATION_PER_PAGE))
            ->appends($query);

        return $this->viewResponse->create(ViewName::RULES_LIST, [
            'rules' => $rules,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'query' => $query,
            'types' => AclRuleType::cases(),
            'privileges' => AclPrivilege::cases(),
            'roles' => $this->roleModel->newQuery()->orderBy(AclRoleModel::FIELD_NAME)->get(),
            'resources' => $this->resourceModel->newQuery()->orderBy(AclResourceModel::FIELD_NAME)->get(),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->viewResponse->create(ViewName::RULES_CREATE, [
            'types' => AclRuleType::cases(),
            'privileges' => AclPrivilege::cases(),
            'roles' => $this->roleModel->newQuery()->orderBy(AclRoleModel::FIELD_NAME)->get(),
            'resources' => $this->resourceModel->newQuery()->orderBy(AclResourceModel::FIELD_NAME)->get(),
        ]);
    }

    public function store(StoreRuleRequest $request): ResponseInterface
    {
        /** @var RuleDto $dto */
        $dto = $request->toDto();
        $this->ruleModel->newQuery()->create($this->ruleAttributesMapper->toAttributes($dto));
        $this->flashBag->addSuccess(self::MSG_RULE_CREATED);

        return $this->response->redirectByName(RouteName::RULES);
    }

    public function edit(int $id): ResponseInterface
    {
        $rule = $this->ruleModel
            ->newQuery()
            ->with(['role', 'resource'])
            ->find($id);

        if (!($rule instanceof AclRuleModel)) {
            $this->flashBag->addError(self::MSG_RULE_NOT_FOUND);

            return $this->response->redirectByName(RouteName::RULES);
        }

        return $this->viewResponse->create(ViewName::RULES_EDIT, [
            'rule' => $rule,
            'types' => AclRuleType::cases(),
            'privileges' => AclPrivilege::cases(),
            'roles' => $this->roleModel->newQuery()->orderBy(AclRoleModel::FIELD_NAME)->get(),
            'resources' => $this->resourceModel->newQuery()->orderBy(AclResourceModel::FIELD_NAME)->get(),
        ]);
    }

    public function update(UpdateRuleRequest $request, int $id): ResponseInterface
    {
        $rule = $this->ruleModel->newQuery()->find($id);
        if (!($rule instanceof AclRuleModel)) {
            $this->flashBag->addError(self::MSG_RULE_NOT_FOUND);

            return $this->response->redirectByName(RouteName::RULES);
        }

        /** @var RuleDto $dto */
        $dto = $request->toDto();
        $rule->update($this->ruleAttributesMapper->toAttributes($dto));
        $this->flashBag->addSuccess(self::MSG_RULE_UPDATED);

        return $this->response->redirectByName(RouteName::RULES);
    }

    public function destroy(int $id): ResponseInterface
    {
        $rule = $this->ruleModel->newQuery()->find($id);
        if (!($rule instanceof AclRuleModel)) {
            $this->flashBag->addError(self::MSG_RULE_NOT_FOUND);

            return $this->response->redirectByName(RouteName::RULES);
        }

        $rule->delete();
        $this->flashBag->addSuccess(self::MSG_RULE_DELETED);

        return $this->response->redirectByName(RouteName::RULES);
    }
}
