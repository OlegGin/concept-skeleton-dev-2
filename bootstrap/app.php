<?php declare(strict_types=1);

use Concept\App\Bootstrap\ApplicationRuntimeBootstrap;
use Concept\App\Bootstrap\ApplicationStackBootstrap;
use Concept\App\Bootstrap\EarlyErrorHandlingBootstrap;
use Concept\App\Bootstrap\FoundationBootstrap;
use Concept\Core\App;
use League\Container\Container;

$app = App::create();

/** @var array<string, string> $pathMap */
$pathMap = require __DIR__ . '/path-map.php';
$root = dirname(__DIR__);

/** @var Container $container */
$container = $app->getContainer();
$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$fallbackFilePath = $root . '/resources/views/errors/fallback/500.php';

(new EarlyErrorHandlingBootstrap($debug, $fallbackFilePath))->boot($container);
(new FoundationBootstrap($root, $pathMap))->boot($container);
(new ApplicationStackBootstrap())->boot($container);
(new ApplicationRuntimeBootstrap())->boot($container);

return $app;
