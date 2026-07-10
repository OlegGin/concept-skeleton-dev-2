<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationRuntimeServiceProvider;
use Concept\App\Providers\Layers\ConsoleLayerProvider;
use Concept\App\Providers\Layers\DatabaseLayerProvider;
use Concept\App\Providers\Layers\JsonErrorHandlingLayerProvider;
use Concept\App\Providers\Layers\FoundationLayerProvider;
use Concept\App\Providers\Layers\HttpLayerProvider;
use Concept\App\Providers\Layers\LoggingLayerProvider;
use Concept\App\Providers\Layers\TelemetryLayerProvider;
use Concept\App\Providers\Layers\ValidationLayerProvider;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * API profile: Foundation → Logging → Telemetry → Validation → Database → Http
 * → Console → JsonErrorHandling → Runtime.
 * No Session/CSRF, View, or Components.
 *
 * @param string $root
 * @return list<ServiceProviderInterface>
 */
return function(string $root): array {
    /** @var array<string, string> $pathMap */
    $pathMap = require __DIR__ . '/../../shared/path-map.php';

    return [
        new FoundationLayerProvider($root, $pathMap),
        new LoggingLayerProvider(),
        new TelemetryLayerProvider(),
        new ValidationLayerProvider(),
        new DatabaseLayerProvider(),
        new HttpLayerProvider(routePaths: ['routes/api.php']),
        new ConsoleLayerProvider(),
        new JsonErrorHandlingLayerProvider(),
        new ApplicationRuntimeServiceProvider(),
    ];
};
