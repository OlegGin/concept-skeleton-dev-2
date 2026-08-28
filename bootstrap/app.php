<?php declare(strict_types=1);

use Concept\App\Bootstrap\ApplicationStackBootstrap;
use Concept\Core\App;
use League\Container\Container;

$app = App::create();


/** @var Container $container */
$container = $app->getContainer();

(new ApplicationStackBootstrap())->boot($container);

return $app;
