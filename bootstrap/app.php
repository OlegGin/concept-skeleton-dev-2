<?php declare(strict_types=1);

use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsBootstrap;
use League\Container\Container;
use Whoops\Run as Whoops;

$root = dirname(__DIR__);

$app = App::create();
/** @var Container $container */
$container = $app->getContainer();
$errorsFallbackPath = $root . '/resources/views/errors/fallback';
$container->add(Whoops::class, EarlyWhoopsBootstrap::register($errorsFallbackPath))->setShared(true);

/** @var callable(string): list<callable> $providersFactory */
$providersFactory = require $root . '/bootstrap/providers.php';
$app->registerServiceProviders($providersFactory($root));

return $app;
