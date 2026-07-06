<?php declare(strict_types=1);

use Concept\App\Controllers\IndexController;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */
$router->group('', function(RouteGroup $router): void {
    $router->get('/', [IndexController::class, 'index'])->setName('home');
});
