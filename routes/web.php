<?php declare(strict_types=1);

use Concept\App\Controllers\IndexController;
use Concept\App\Middleware\HandleCsrfExceptionMiddleware;
use Concept\App\Middleware\HandleValidationExceptionMiddleware;
use Concept\App\Middleware\ShareViewDataMiddleware;
use Concept\Extensions\Csrf\Middleware\VerifyCsrfTokenMiddleware;
use Concept\Extensions\Web\Middleware\StorePreviousUrlMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */
$router->group('', function (RouteGroup $router): void {
    $router->get('/', [IndexController::class, 'index'])->setName('home');
    $router->post('/login', [IndexController::class, 'login'])->setName('login');
})->lazyMiddlewares([
    StorePreviousUrlMiddleware::class,
    HandleValidationExceptionMiddleware::class,
    HandleCsrfExceptionMiddleware::class,
    VerifyCsrfTokenMiddleware::class,
    ShareViewDataMiddleware::class,
]);
