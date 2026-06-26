<?php declare(strict_types=1);

use Concept\Components\AuthAdmin\Middlewares\AuthMiddleware;
use Concept\Components\SettingsManager\Controllers\SettingsController;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */

$router->group('/admin/settings', function (RouteGroup $route) {
    $route->get('/', [SettingsController::class, 'index'])->setName('admin.settings');
    $route->get('/create', [SettingsController::class, 'create'])->setName('admin.settings.create');
    $route->post('/store', [SettingsController::class, 'store'])->setName('admin.settings.store');
    $route->get('/edit/{id:number}', [SettingsController::class, 'edit'])->setName('admin.settings.edit');
    $route->post('/update/{id:number}', [SettingsController::class, 'update'])->setName('admin.settings.update');
    $route->post('/delete/{id:number}', [SettingsController::class, 'destroy'])->setName('admin.settings.destroy');
})->lazyMiddleware(AuthMiddleware::class);
