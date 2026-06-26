<?php declare(strict_types=1);

namespace Concept\Components\Telemetry;

use Concept\Components\Telemetry\Providers\TelemetryServiceProvider;
use Concept\Components\Telemetry\Subscribers\ComponentsTelemetrySubscriber;
use Concept\Components\Telemetry\Subscribers\DatabaseTelemetrySubscriber;
use Concept\Components\Telemetry\Subscribers\FormRequestTelemetrySubscriber;
use Concept\Components\Telemetry\Subscribers\ViewTelemetrySubscriber;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\Components\Contracts\EventSubscriberContributorInterface;
use League\Event\ListenerSubscriber;

final class TelemetryComponent implements ComponentInterface, EventSubscriberContributorInterface
{
    private const string NAME = 'Telemetry';
    private const string VERSION = '1.0.0';
    private const string DESCRIPTION = 'Telemetry glue — event subscribers that fill TelemetryCollector.';

    public function name(): string
    {
        return self::NAME;
    }

    public function version(): string
    {
        return self::VERSION;
    }

    public function description(): string
    {
        return self::DESCRIPTION;
    }

    public function routes(): ?string
    {
        return null;
    }

    public function providers(): array
    {
        return [
            TelemetryServiceProvider::class,
        ];
    }

    public function viewExtensions(): array
    {
        return [];
    }

    public function viewPaths(): array
    {
        return [];
    }

    public function viewContexts(): array
    {
        return [];
    }

    public function commands(): array
    {
        return [];
    }

    public function seeders(): array
    {
        return [];
    }

    public function migrationPaths(): array
    {
        return [];
    }

    public function assets(): array
    {
        return [];
    }

    /**
     * @return list<class-string<ListenerSubscriber>>
     */
    public function eventSubscribers(): array
    {
        return [
            DatabaseTelemetrySubscriber::class,
            FormRequestTelemetrySubscriber::class,
            ViewTelemetrySubscriber::class,
            ComponentsTelemetrySubscriber::class,
        ];
    }
}
