<?php declare(strict_types=1);

use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsBootstrap;
use Concept\testApp\jsonDbApp\src\Providers\JsonDbAppServiceProvider;
use League\Container\Container;
use Whoops\Run as Whoops;

$root = dirname(__DIR__);

$app = App::create();
/** @var Container $container */
$container = $app->getContainer();
$container->add(Whoops::class, EarlyWhoopsBootstrap::register($root))->setShared(true);

$app->registerServiceProviders([fn() => new JsonDbAppServiceProvider($root)]);

return $app;
