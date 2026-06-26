<?php declare(strict_types=1);

namespace Concept\Components\Acl\Controllers\Admin;

use Concept\Components\Acl\Constants\RouteName;
use Concept\Components\Acl\Constants\ViewName;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Requests\Matrix\UpdateMatrixAccessRequest;
use Concept\Components\Acl\Services\AclMatrixService;
use Concept\Extensions\Http\Contracts\UrlGeneratorInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MatrixController
{
    private const string MSG_ACCESS_UPDATED = 'Access updated.';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FlashBagInterface $flashBag,
        private readonly AclMatrixService $matrixService,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $privilegeParam = $request->getQueryParams()['privilege'] ?? null;
        $privilege = is_string($privilegeParam) && $privilegeParam !== ''
            ? AclPrivilege::tryFrom($privilegeParam)?->value
            : null;

        $matrix = $this->matrixService->build($privilege);

        return $this->viewResponse->create(ViewName::MATRIX_INDEX, [
            'roles' => $matrix['roles'],
            'resources' => $matrix['resources'],
            'cells' => $matrix['cells'],
            'privilege' => $privilege,
            'privileges' => AclPrivilege::cases(),
        ]);
    }

    public function update(UpdateMatrixAccessRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $privilege = $data['privilege'] ?? null;
        $privilege = is_string($privilege) && $privilege !== '' ? $privilege : null;

        $roleId = $data['role_id'] ?? null;
        $resourceId = $data['resource_id'] ?? null;
        $action = $data['action'] ?? null;

        if (!is_numeric($roleId) || !is_numeric($resourceId) || !is_string($action)) {
            return $this->response->redirectByName(RouteName::MATRIX);
        }

        $this->matrixService->setAccess(
            (int) $roleId,
            (int) $resourceId,
            $action,
            $privilege,
        );

        $this->flashBag->addSuccess(self::MSG_ACCESS_UPDATED);

        $url = $this->urlGenerator->uri(RouteName::MATRIX);
        if ($privilege !== null) {
            $url .= '?' . http_build_query(['privilege' => $privilege]);
        }

        return $this->response->redirect($url);
    }
}
