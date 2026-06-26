<?php declare(strict_types=1);

namespace Concept\Components\Acl\Controllers\Admin;

use Concept\Components\Acl\Constants\RouteName;
use Concept\Components\Acl\Constants\ViewName;
use Concept\Components\Acl\Dto\Resource\ResourceDto;
use Concept\Components\Acl\Mappers\ResourceAttributesMapper;
use Concept\Components\Acl\Models\AclResourceModel;
use Concept\Components\Acl\Requests\Resources\StoreResourceRequest;
use Concept\Components\Acl\Requests\Resources\UpdateResourceRequest;
use Concept\Components\Acl\Support\AclListQuery;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Services\Session\Contracts\FlashBagInterface;
use Concept\Core\Services\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResourceController
{
    private const string CONFIG_PAGINATION_PER_PAGE = 'pagination.per_page';
    private const int DEFAULT_PAGINATION_PER_PAGE = 10;

    private const string MSG_RESOURCE_CREATED = 'Resource created successfully.';
    private const string MSG_RESOURCE_UPDATED = 'Resource updated successfully.';
    private const string MSG_CANNOT_DELETE_RESOURCE = 'Cannot delete a resource that has child resources, rules, or route rules.';
    private const string MSG_RESOURCE_DELETED = 'Resource deleted successfully.';
    private const string MSG_RESOURCE_NOT_FOUND = 'Resource not found.';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly FlashBagInterface $flashBag,
        private readonly ConfigInterface $config,
        private readonly AclResourceModel $resourceModel,
        private readonly ResourceAttributesMapper $resourceAttributesMapper,
        private readonly AclListQuery $listQuery,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $params */
        $params = $request->getQueryParams();

        $filters = $this->listQuery->resourceFilters($params);
        [$sortBy, $sortDirection] = $this->listQuery->resourceSort($params);
        $query = $this->listQuery->viewContext($filters, $sortBy, $sortDirection);

        $builder = $this->resourceModel
            ->newQuery()
            ->with('parent')
            ->withCount(['children', 'rules']);

        $this->listQuery->applyResourceFilters($builder, $filters);
        $this->listQuery->applyResourceSort($builder, $sortBy, $sortDirection);

        $resources = $builder
            ->paginate($this->config->getInt(self::CONFIG_PAGINATION_PER_PAGE, self::DEFAULT_PAGINATION_PER_PAGE))
            ->appends($query);

        return $this->viewResponse->create(ViewName::RESOURCES_LIST, [
            'resources' => $resources,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'query' => $query,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->viewResponse->create(ViewName::RESOURCES_CREATE, [
            'resources' => $this->parentOptions(),
        ]);
    }

    public function store(StoreResourceRequest $request): ResponseInterface
    {
        /** @var ResourceDto $dto */
        $dto = $request->toDto();
        $this->resourceModel->newQuery()->create($this->resourceAttributesMapper->toAttributes($dto));
        $this->flashBag->addSuccess(self::MSG_RESOURCE_CREATED);

        return $this->response->redirectByName(RouteName::RESOURCES);
    }

    public function edit(int $id): ResponseInterface
    {
        $resource = $this->findResource($id);
        if ($resource === null) {
            return $this->redirectNotFound();
        }

        return $this->viewResponse->create(ViewName::RESOURCES_EDIT, [
            'resource' => $resource,
            'resources' => $this->parentOptions($id),
        ]);
    }

    public function update(UpdateResourceRequest $request, int $id): ResponseInterface
    {
        $resource = $this->findResource($id);
        if ($resource === null) {
            return $this->redirectNotFound();
        }

        /** @var ResourceDto $dto */
        $dto = $request->toDto();
        $resource->update($this->resourceAttributesMapper->toAttributes($dto));
        $this->flashBag->addSuccess(self::MSG_RESOURCE_UPDATED);

        return $this->response->redirectByName(RouteName::RESOURCES);
    }

    public function destroy(int $id): ResponseInterface
    {
        $resource = $this->findResource($id);
        if ($resource === null) {
            return $this->redirectNotFound();
        }

        if ($resource->children()->exists() || $resource->rules()->exists() || $resource->routeRules()->exists()) {
            $this->flashBag->addError(self::MSG_CANNOT_DELETE_RESOURCE);

            return $this->response->redirectByName(RouteName::RESOURCES);
        }

        $resource->delete();
        $this->flashBag->addSuccess(self::MSG_RESOURCE_DELETED);

        return $this->response->redirectByName(RouteName::RESOURCES);
    }

    private function findResource(int $id): ?AclResourceModel
    {
        $resource = $this->resourceModel->newQuery()->find($id);

        return $resource instanceof AclResourceModel ? $resource : null;
    }

    /**
     * @return list<AclResourceModel>
     */
    private function parentOptions(?int $excludeId = null): array
    {
        $query = $this->resourceModel
            ->newQuery()
            ->orderBy(AclResourceModel::FIELD_NAME);

        if ($excludeId !== null) {
            $query->where(AclResourceModel::FIELD_ID, '!=', $excludeId);
        }

        /** @var list<AclResourceModel> */
        return $query->get()->all();
    }

    private function redirectNotFound(): ResponseInterface
    {
        $this->flashBag->addError(self::MSG_RESOURCE_NOT_FOUND);

        return $this->response->redirectByName(RouteName::RESOURCES);
    }
}
