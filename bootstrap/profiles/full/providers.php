<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationRuntimeServiceProvider;
use Concept\App\Providers\Layers\ConsoleLayerProvider;
use Concept\App\Providers\Layers\DatabaseLayerProvider;
use Concept\App\Providers\Layers\ErrorHandlingLayerProvider;
use Concept\App\Providers\Layers\FoundationLayerProvider;
use Concept\App\Providers\Layers\HttpLayerProvider;
use Concept\App\Providers\Layers\LoggingLayerProvider;
use Concept\App\Providers\Layers\ValidationLayerProvider;
use Concept\App\Providers\Layers\ViewLayerProvider;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * @param string $root
 * @return list<ServiceProviderInterface>
 */
return function(string $root): array {
    /** @var array<string, string> $pathMap */
    $pathMap = require __DIR__ . '/../../shared/path-map.php';

    return [
        new FoundationLayerProvider($root, $pathMap),
        new LoggingLayerProvider(),
        new ValidationLayerProvider(),
        new DatabaseLayerProvider(),
        new HttpLayerProvider(),
        new ConsoleLayerProvider(),
        new ViewLayerProvider(),
        new ErrorHandlingLayerProvider(),
        new ApplicationRuntimeServiceProvider(),
    ];
};
