<?php declare(strict_types=1);

use Concept\Components\AuthAdmin\Middlewares\AuthMiddleware;
use Concept\Components\SystemLog\Controllers\SystemLogController;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */

$router->group('/admin/system-log', function (RouteGroup $route): void {
    $route->get('/', [SystemLogController::class, 'index'])->setName('admin.system_log');
    $route->get('/show/{id:number}', [SystemLogController::class, 'show'])->setName('admin.system_log.show');
})->lazyMiddleware(AuthMiddleware::class);
