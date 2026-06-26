<?php declare(strict_types=1);

use Concept\Core\App;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\EventDispatcher\EventDispatcherInterface;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$container = $app->getContainer();
$dispatcher = $container->has(EventDispatcherInterface::class)
    ? $container->get(EventDispatcherInterface::class)
    : null;

$response = $app->handle($dispatcher instanceof EventDispatcherInterface ? $dispatcher : null);

(new SapiEmitter)->emit($response);
