<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Pagination;

use Illuminate\Pagination\Paginator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PaginatorConfigurator
{
    public static function configure(ContainerInterface $container, string $pageName): void
    {
        Paginator::currentPageResolver(function(?string $resolvedPageName = null) use ($container, $pageName): int {
            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);
            $params = $request->getQueryParams();
            $queryKey = $resolvedPageName ?? $pageName;

            return (int) ($params[$queryKey] ?? 1);
        });

        Paginator::currentPathResolver(function() use ($container): string {
            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);

            return $request->getUri()->getPath();
        });
    }
}
