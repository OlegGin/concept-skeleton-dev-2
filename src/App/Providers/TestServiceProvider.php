<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\Core\App;
use League\Container\ServiceProvider\AbstractServiceProvider;

class TestServiceProvider extends AbstractServiceProvider
{
    public function __construct(private readonly App $app) {}

    public function provides(string $id): bool
    {
        $services = [
            'test',
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add('test', function () use ($container) {
            return 'test';
        })->setShared(true);
    }
}
