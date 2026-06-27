<?php declare(strict_types=1);

use Concept\testApp\jsonApp\src\Controllers\ApiController;
use Concept\Extensions\Json\Middleware\ForceJsonResponseMiddleware;
use Concept\Extensions\Json\Middleware\ParseJsonBodyMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */
$router->group('/api', function(RouteGroup $router): void {
    $router->get('/ping', [ApiController::class, 'ping'])->setName('api.ping');
    $router->post('/echo', [ApiController::class, 'echo'])->setName('api.echo');
})->lazyMiddlewares([
    ParseJsonBodyMiddleware::class,
    ForceJsonResponseMiddleware::class,
]);
