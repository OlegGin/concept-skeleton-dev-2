<?php declare(strict_types=1);

use Concept\Components\DebugBar\Middlewares\DebugBarMiddleware;
use League\Container\Container;
use League\Route\Router;

/** @var Container $container */
/** @var Router $router */

$router->lazyMiddleware(DebugBarMiddleware::class);
