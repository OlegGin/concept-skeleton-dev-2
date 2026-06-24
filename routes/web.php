<?php declare(strict_types=1);

use Concept\App\Controllers\IndexController;
use Concept\App\Middleware\HandleValidationExceptionMiddleware;
use Concept\App\Middleware\ShareViewDataMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */
$router->group('', function (RouteGroup $router): void {
    $router->get('/', [IndexController::class, 'index'])->setName('home');
    $router->post('/login', [IndexController::class, 'login'])->setName('login');
})->lazyMiddlewares([
    HandleValidationExceptionMiddleware::class,
    ShareViewDataMiddleware::class,
]);
