<?php declare(strict_types=1);

namespace Concept\Components\Health\Contracts;

use Concept\Components\Health\HealthResult;

interface HealthCheckInterface
{
    public function name(): string;

    public function run(): HealthResult;
}
