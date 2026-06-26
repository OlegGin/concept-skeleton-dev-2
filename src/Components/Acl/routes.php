<?php declare(strict_types=1);

use Concept\Components\Acl\Controllers\Admin\MatrixController;
use Concept\Components\Acl\Controllers\Admin\ResourceController;
use Concept\Components\Acl\Controllers\Admin\RoleController;
use Concept\Components\Acl\Controllers\Admin\RouteRuleController;
use Concept\Components\Acl\Controllers\Admin\RuleController;
use Concept\Components\AuthAdmin\Middlewares\AuthMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/** @var Router $router */

$router->group('/admin/acl/matrix', function (RouteGroup $route) {
    $route->get('/', [MatrixController::class, 'index'])->setName('admin.acl.matrix');
    $route->post('/update', [MatrixController::class, 'update'])->setName('admin.acl.matrix.update');
})->lazyMiddleware(AuthMiddleware::class);

$router->group('/admin/acl/roles', function (RouteGroup $route) {
    $route->get('/', [RoleController::class, 'index'])->setName('admin.acl.roles');
    $route->get('/create', [RoleController::class, 'create'])->setName('admin.acl.role.create');
    $route->post('/store', [RoleController::class, 'store'])->setName('admin.acl.role.store');
    $route->get('/edit/{id:number}', [RoleController::class, 'edit'])->setName('admin.acl.role.edit');
    $route->post('/update/{id:number}', [RoleController::class, 'update'])->setName('admin.acl.role.update');
    $route->post('/delete/{id:number}', [RoleController::class, 'destroy'])->setName('admin.acl.role.destroy');
})->lazyMiddleware(AuthMiddleware::class);

$router->group('/admin/acl/resources', function (RouteGroup $route) {
    $route->get('/', [ResourceController::class, 'index'])->setName('admin.acl.resources');
    $route->get('/create', [ResourceController::class, 'create'])->setName('admin.acl.resource.create');
    $route->post('/store', [ResourceController::class, 'store'])->setName('admin.acl.resource.store');
    $route->get('/edit/{id:number}', [ResourceController::class, 'edit'])->setName('admin.acl.resource.edit');
    $route->post('/update/{id:number}', [ResourceController::class, 'update'])->setName('admin.acl.resource.update');
    $route->post('/delete/{id:number}', [ResourceController::class, 'destroy'])->setName('admin.acl.resource.destroy');
})->lazyMiddleware(AuthMiddleware::class);

$router->group('/admin/acl/rules', function (RouteGroup $route) {
    $route->get('/', [RuleController::class, 'index'])->setName('admin.acl.rules');
    $route->get('/create', [RuleController::class, 'create'])->setName('admin.acl.rule.create');
    $route->post('/store', [RuleController::class, 'store'])->setName('admin.acl.rule.store');
    $route->get('/edit/{id:number}', [RuleController::class, 'edit'])->setName('admin.acl.rule.edit');
    $route->post('/update/{id:number}', [RuleController::class, 'update'])->setName('admin.acl.rule.update');
    $route->post('/delete/{id:number}', [RuleController::class, 'destroy'])->setName('admin.acl.rule.destroy');
})->lazyMiddleware(AuthMiddleware::class);

$router->group('/admin/acl/route-rules', function (RouteGroup $route) {
    $route->get('/', [RouteRuleController::class, 'index'])->setName('admin.acl.route-rules');
    $route->get('/create', [RouteRuleController::class, 'create'])->setName('admin.acl.route-rule.create');
    $route->post('/store', [RouteRuleController::class, 'store'])->setName('admin.acl.route-rule.store');
    $route->get('/edit/{id:number}', [RouteRuleController::class, 'edit'])->setName('admin.acl.route-rule.edit');
    $route->post('/update/{id:number}', [RouteRuleController::class, 'update'])->setName('admin.acl.route-rule.update');
    $route->post('/delete/{id:number}', [RouteRuleController::class, 'destroy'])->setName('admin.acl.route-rule.destroy');
})->lazyMiddleware(AuthMiddleware::class);
