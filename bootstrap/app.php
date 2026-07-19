<?php declare(strict_types=1);

use Concept\App\Bootstrap\ApplicationRuntimeBootstrap;
use Concept\App\Bootstrap\ApplicationStackBootstrap;
use Concept\App\Bootstrap\EarlyErrorHandlingBootstrap;
use Concept\App\Bootstrap\FoundationBootstrap;
use Concept\Core\App;

$app = App::create();

/** @var array<string, string> $pathMap */
$pathMap = require __DIR__ . '/path-map.php';
$root = dirname(__DIR__);

$app->registerServiceProviders([
    new EarlyErrorHandlingBootstrap(
        debug: ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        fallbackFilePath: $root . '/resources/views/errors/fallback/500.php',
    ),
    new FoundationBootstrap($root, $pathMap),
    new ApplicationStackBootstrap(),
    new ApplicationRuntimeBootstrap(),
]);

return $app;
