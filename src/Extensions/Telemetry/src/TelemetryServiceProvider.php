<?php declare(strict_types=1);

namespace Concept\Extensions\Telemetry;

use League\Container\ServiceProvider\AbstractServiceProvider;

final class TelemetryServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === TelemetryCollector::class;
    }

    public function register(): void
    {
        $this->getContainer()
            ->add(TelemetryCollector::class, fn(): TelemetryCollector => new TelemetryCollector())
            ->setShared(true);
    }
}
