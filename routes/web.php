<?php declare(strict_types=1);

use Concept\App\Controllers\IndexController;
use Concept\App\Controllers\TestController;
use Concept\App\Middleware\HandleHttpErrorMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */
$router->lazyMiddlewares([
    HandleHttpErrorMiddleware::class,
]);

$router->group('', function(RouteGroup $router): void {
    $router->get('/', [IndexController::class, 'index'])->setName('home');

    $router->get('/test/boom', [TestController::class, 'boom'])->setName('test.boom');
    $router->get('/test/http-error', [TestController::class, 'httpError'])->setName('test.http_error');
    $router->get('/test/hello/{name}', [TestController::class, 'hello'])->setName('test.hello');
    $router->get('/test/user/{id}', [TestController::class, 'user'])->setName('test.user');
});
