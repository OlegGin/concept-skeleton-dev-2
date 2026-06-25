<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationServiceProvider;
use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsBootstrap;
use League\Container\Container;
use Whoops\Run as Whoops;

require __DIR__ . '/env.php';

$root = dirname(__DIR__);
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

$app = App::create();
/** @var Container $container */
$container = $app->getContainer();
$container->add(Whoops::class, EarlyWhoopsBootstrap::register($root, $debug))->setShared(true);

$app->registerServiceProviders([
    fn () => new ApplicationServiceProvider($root),
]);

return $app;
