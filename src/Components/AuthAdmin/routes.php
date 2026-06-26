<?php declare(strict_types=1);

use Concept\Components\AuthAdmin\Controllers\AdminController;
use Concept\Components\AuthAdmin\Controllers\UserController;
use Concept\Components\AuthAdmin\Middlewares\AuthMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */

$router->get('/admin/login', [AdminController::class, 'showLogin'])->setName('admin.login');
$router->post('/admin/login', [AdminController::class, 'login'])->setName('admin.login.submit');
$router->group('/admin', function (RouteGroup $route) {
    $route->get('/', [AdminController::class, 'index'])->setName('admin.home');
    $route->get('/dashboard', [AdminController::class, 'index'])->setName('admin.dashboard');
    $route->get('/logout', [AdminController::class, 'logout'])->setName('admin.logout');
})->lazyMiddleware(AuthMiddleware::class);

$router->group('/admin/users', function (RouteGroup $route) {
    $route->get('/', [UserController::class, 'index'])->setName('admin.users');
    $route->get('/show/{id:number}', [UserController::class, 'show'])->setName('admin.user.show');
    $route->get('/create', [UserController::class, 'create'])->setName('admin.user.create');
    $route->post('/store', [UserController::class, 'store'])->setName('admin.user.store');
    $route->get('/edit/{id:number}', [UserController::class, 'edit'])->setName('admin.user.edit');
    $route->post('/update/{id:number}', [UserController::class, 'update'])->setName('admin.user.update');
    $route->post('/password/{id:number}', [UserController::class, 'updatePassword'])->setName('admin.user.password');
    $route->post('/delete/{id:number}', [UserController::class, 'destroy'])->setName('admin.user.destroy');
    $route->get('/generate-token-api', [UserController::class, 'generateTokenApi'])->setName('admin.users.generate-token-api');
})->lazyMiddleware(AuthMiddleware::class);
