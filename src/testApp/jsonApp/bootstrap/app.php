<?php declare(strict_types=1);

use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsBootstrap;
use Concept\testApp\jsonApp\src\Foundation\PathName;
use Concept\testApp\jsonApp\src\Providers\JsonAppServiceProvider;
use League\Container\Container;
use Whoops\Run as Whoops;

$root = dirname(__DIR__);

/** @var array<string, string> $paths */
$paths = [
    PathName::BOOTSTRAP => 'bootstrap',
    PathName::SRC => 'src',
    PathName::CONFIG => 'config',
    PathName::PUBLIC => 'public',
    PathName::STORAGE => 'storage',
    PathName::LOGS => 'storage/logs',
];

$app = App::create();
/** @var Container $container */
$container = $app->getContainer();
$container->add(Whoops::class, EarlyWhoopsBootstrap::register($root))->setShared(true);

$app->registerServiceProviders([fn() => new JsonAppServiceProvider($root, $paths)]);

return $app;