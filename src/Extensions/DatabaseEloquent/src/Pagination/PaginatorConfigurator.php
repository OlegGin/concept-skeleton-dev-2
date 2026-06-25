<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Pagination;

use Illuminate\Pagination\Paginator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PaginatorConfigurator
{
    public static function configure(ContainerInterface $container): void
    {
        Paginator::currentPageResolver(function ($pageName = 'page') use ($container): int {
            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);
            $params = $request->getQueryParams();

            return (int) ($params[$pageName] ?? 1);
        });

        Paginator::currentPathResolver(function () use ($container): string {
            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);

            return $request->getUri()->getPath();
        });
    }
}
