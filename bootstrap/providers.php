<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationComponentsServiceProvider;
use Concept\App\Providers\ApplicationRuntimeServiceProvider;
use Concept\App\Providers\Layers\ConsoleLayerProvider;
use Concept\App\Providers\Layers\DatabaseLayerProvider;
use Concept\App\Providers\Layers\TwigErrorHandlingLayerProvider;
use Concept\App\Providers\Layers\FoundationLayerProvider;
use Concept\App\Providers\Layers\HttpLayerProvider;
use Concept\App\Providers\Layers\LoggingLayerProvider;
use Concept\App\Providers\Layers\SessionLayerProvider;
use Concept\App\Providers\Layers\TelemetryLayerProvider;
use Concept\App\Providers\Layers\ValidationLayerProvider;
use Concept\App\Providers\Layers\ViewLayerProvider;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * @param string $root
 * @return list<ServiceProviderInterface>
 */
return function(string $root): array {
    /** @var array<string, string> $pathMap */
    $pathMap = require __DIR__ . '/path-map.php';

    return [
        new FoundationLayerProvider($root, $pathMap),
        new LoggingLayerProvider(),
        new TelemetryLayerProvider(),
        new ValidationLayerProvider(),
        new DatabaseLayerProvider(),
        new SessionLayerProvider(),
        new HttpLayerProvider(),
        new ConsoleLayerProvider(),
        new ViewLayerProvider(),
        new TwigErrorHandlingLayerProvider(),
        new ApplicationComponentsServiceProvider(),
        new ApplicationRuntimeServiceProvider(),
    ];
};
