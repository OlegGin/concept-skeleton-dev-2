<?php declare(strict_types=1);

namespace Concept\Components\Health\Checks;

use Concept\Components\Health\Contracts\HealthCheckInterface;
use Concept\Components\Health\HealthResult;
use Concept\Extensions\Components\ComponentRegistry;
use Concept\Extensions\Components\Contracts\ComponentInterface;

final class ComponentsPresentCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly ComponentRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'components';
    }

    public function run(): HealthResult
    {
        $names = array_map(
            static fn(ComponentInterface $component): string => $component->name(),
            $this->registry->all(),
        );

        if ($names === []) {
            return HealthResult::warn('no components registered');
        }

        sort($names);

        return HealthResult::ok(implode(', ', $names));
    }
}
