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
 * @return list<callable(): ServiceProviderInterface>
 */
return function(string $root): array {
    /** @var array<string, string> $pathMap */
    $pathMap = require __DIR__ . '/../../shared/path-map.php';

    return [
        fn() => new FoundationLayerProvider($root, $pathMap),
        fn() => new LoggingLayerProvider(),
        fn() => new ErrorHandlingLayerProvider(),
        fn() => new ValidationLayerProvider(),
        fn() => new DatabaseLayerProvider(),
        fn() => new HttpLayerProvider(),
        fn() => new ConsoleLayerProvider(),
        fn() => new ViewLayerProvider(),
        fn() => new ApplicationRuntimeServiceProvider(),
    ];
};
