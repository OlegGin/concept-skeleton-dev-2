<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationServiceProvider;
use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsBootstrap;
use League\Container\Container;
use Whoops\Run as Whoops;

$root = dirname(__DIR__);

$app = App::create();
/** @var Container $container */
$container = $app->getContainer();
$container->add(Whoops::class, EarlyWhoopsBootstrap::register($root))->setShared(true);

$app->registerServiceProviders([
    fn() => new ApplicationServiceProvider($root),
]);

return $app;
