<?php declare(strict_types=1);

namespace Concept\Components\Acl\Authorization;

use Concept\Components\Acl\Authorization\Exceptions\AccessDeniedException;
use Concept\Components\Acl\Contracts\AclInterface;
use Concept\Components\Acl\Services\AclRouteRulesService;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use League\Route\Route;
use Psr\Http\Message\ServerRequestInterface;

final class AclRouteAuthorization implements RouteInterceptorInterface
{
    public function __construct(
        private readonly AclInterface $acl,
        private readonly AclRouteRulesService $routeRules,
        private readonly ConfigInterface $config,
    ) {}

    public function intercept(Route $route, ServerRequestInterface $request): void
    {
        $routeName = $route->getName();
        if (!is_string($routeName) || $routeName === '') {
            return;
        }

        $rule = $this->routeRules->resolve($routeName);
        if ($rule === null) {
            return;
        }

        $resource = $rule['resource'];
        $privilege = $rule['privilege'] ?? null;

        if ($this->acl->isAllowed($resource, $privilege)) {
            return;
        }

        $redirectRouteName = $rule['redirect_route_name'] ?? null;
        if (!is_string($redirectRouteName) || $redirectRouteName === '') {
            $redirectRouteName = $this->config->getString('acl.redirect_route_name', 'admin.dashboard');
        }

        throw new AccessDeniedException(
            sprintf('Access denied for route [%s]', $routeName),
            $redirectRouteName,
        );
    }
}
