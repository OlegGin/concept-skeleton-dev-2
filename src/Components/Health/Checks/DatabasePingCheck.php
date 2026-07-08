<?php declare(strict_types=1);

namespace Concept\Components\Health\Checks;

use Concept\Components\Health\Contracts\HealthCheckInterface;
use Concept\Components\Health\HealthResult;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Psr\Container\ContainerInterface;
use Throwable;

final class DatabasePingCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function name(): string
    {
        return 'database';
    }

    public function run(): HealthResult
    {
        if (!$this->container->has(CapsuleManager::class)) {
            return HealthResult::skip('CapsuleManager not registered');
        }

        try {
            /** @var CapsuleManager $capsule */
            $capsule = $this->container->get(CapsuleManager::class);
            $capsule->getConnection()->select('select 1');

            return HealthResult::ok('connected');
        } catch (Throwable $e) {
            return HealthResult::fail($e->getMessage());
        }
    }
}
