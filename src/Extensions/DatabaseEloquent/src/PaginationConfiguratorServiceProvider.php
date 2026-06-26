<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent;

use Concept\Extensions\DatabaseEloquent\Pagination\PaginatorConfigurator;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class PaginationConfiguratorServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function __construct(
        private readonly string $pageName = 'page',
    ) {}

    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        PaginatorConfigurator::configure($this->getContainer(), $this->pageName);
    }
}
