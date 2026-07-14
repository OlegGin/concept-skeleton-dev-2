<?php declare(strict_types=1);

use Concept\App\Controllers\StackTestController;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */
$router->group('/stack', function(RouteGroup $router): void {
    $router->get('/', [StackTestController::class, 'index'])->setName('stack.index');
    $router->get('/ping', [StackTestController::class, 'ping'])->setName('stack.ping');
    $router->get('/log', [StackTestController::class, 'log'])->setName('stack.log');
    $router->get('/session', [StackTestController::class, 'session'])->setName('stack.session');
    $router->get('/db', [StackTestController::class, 'db'])->setName('stack.db');
    $router->get('/view', [StackTestController::class, 'view'])->setName('stack.view');
    $router->get('/hello/{name}', [StackTestController::class, 'hello'])->setName('stack.hello');
    $router->get('/user/{id}', [StackTestController::class, 'user'])->setName('stack.user');
    $router->post('/echo', [StackTestController::class, 'echo'])->setName('stack.echo');
});
