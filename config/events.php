<?php declare(strict_types=1);

use Concept\App\Telemetry\Subscribers\ComponentsTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\DatabaseTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\ExtensionsTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\FormRequestTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\TelemetryEventSubscriber;
use Concept\App\Telemetry\Subscribers\ViewTelemetrySubscriber;

return [
    'events' => [
        'enabled' => false,
        'subscribers' => [
            ComponentsTelemetrySubscriber::class,
            DatabaseTelemetrySubscriber::class,
            ExtensionsTelemetrySubscriber::class,
            FormRequestTelemetrySubscriber::class,
            ViewTelemetrySubscriber::class,
            TelemetryEventSubscriber::class,
        ],
    ],
];
