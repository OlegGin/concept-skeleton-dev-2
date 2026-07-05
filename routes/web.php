<?php declare(strict_types=1);

use Concept\App\Controllers\IndexController;
use Concept\App\Controllers\TestController;
use Concept\App\Middleware\HandleHttpErrorMiddleware;
use Concept\App\Middleware\HandleValidationExceptionMiddleware;
use Concept\App\Middleware\ShareViewDataMiddleware;
use Concept\App\Middleware\StorePreviousUrlMiddleware;
use Concept\Extensions\Csrf\Middleware\HandleCsrfExceptionMiddleware;
use Concept\Extensions\Csrf\Middleware\VerifyCsrfTokenMiddleware;
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
    $router->get('/test/db', [TestController::class, 'db'])->setName('test.db');
    $router->post('/test/echo', [TestController::class, 'echo'])->setName('test.echo');
})->lazyMiddlewares([
    StorePreviousUrlMiddleware::class,
    HandleValidationExceptionMiddleware::class,
    HandleCsrfExceptionMiddleware::class,
    VerifyCsrfTokenMiddleware::class,
    ShareViewDataMiddleware::class,
]);
