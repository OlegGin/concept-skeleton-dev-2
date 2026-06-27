<?php declare(strict_types=1);

use Concept\testApp\jsonDbApp\src\Controllers\ApiController;
use Concept\Extensions\Json\Middleware\ForceJsonResponseMiddleware;
use Concept\Extensions\Json\Middleware\ParseJsonBodyMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */
$router->group('/api', function(RouteGroup $router): void {
    $router->get('/ping', [ApiController::class, 'ping'])->setName('api.ping');
    $router->get('/items', [ApiController::class, 'items'])->setName('api.items.index');
    $router->post('/items', [ApiController::class, 'storeItem'])->setName('api.items.store');
})->lazyMiddlewares([
    ParseJsonBodyMiddleware::class,
    ForceJsonResponseMiddleware::class,
]);
