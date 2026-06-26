<?php declare(strict_types=1);

use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsBootstrap;
use JsonApp\Foundation\PathName;
use JsonApp\Providers\JsonAppServiceProvider;
use League\Container\Container;
use Whoops\Run as Whoops;

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'JsonApp\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $root . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

/** @var array<string, string> $paths */
$paths = [
    PathName::BOOTSTRAP => 'bootstrap',
    PathName::SRC => 'src',
    PathName::CONFIG => 'config',
    PathName::PUBLIC => 'public',
    PathName::STORAGE => 'storage',
    PathName::LOGS => 'storage/logs',
];

$app = App::create();
/** @var Container $container */
$container = $app->getContainer();
$container->add(Whoops::class, EarlyWhoopsBootstrap::register($root))->setShared(true);

$app->registerServiceProviders([fn() => new JsonAppServiceProvider($root, $paths)]);

return $app;
