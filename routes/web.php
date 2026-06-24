<?php declare(strict_types=1);

use Concept\App\Controllers\IndexController;
use League\Route\Router;

/** @var Router $router */
$router->get('/', [IndexController::class, 'index'])->setName('home');
$router->get('/edit/{id:number}', [IndexController::class, 'edit'])->setName('edit');
