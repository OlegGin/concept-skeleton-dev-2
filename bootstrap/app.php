<?php declare(strict_types=1);

use Concept\Core\App;

$app = App::create();

$container = $app->getContainer();
$providers = include 'providers.php';
$app->registerServiceProviders($providers);

return $app;
