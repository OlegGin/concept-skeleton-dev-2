<?php declare(strict_types=1);

/**
 * Boot smoke: load application container and resolve core wiring (no DB).
 *
 * Exit 0 on success, 1 on failure.
 */

use Concept\Core\App;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\PathManager\PathManager;
use League\Route\Router;
use Whoops\Run as Whoops;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$failed = false;

function smoke_ok(string $label): void
{
    fwrite(STDOUT, "[OK]   {$label}\n");
}

function smoke_fail(string $label, string $detail): void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, "[FAIL] {$label} — {$detail}\n");
}

try {
    /** @var App $app */
    $app = require $root . '/bootstrap/app.php';
    smoke_ok('bootstrap/app.php');
} catch (Throwable $e) {
    smoke_fail('bootstrap/app.php', $e->getMessage());
    exit(1);
}

$container = $app->getContainer();

$required = [
    Whoops::class => 'early Whoops',
    PathManager::class => 'PathManager',
    ConfigInterface::class => 'ConfigInterface',
    Router::class => 'Router',
];

foreach ($required as $id => $label) {
    try {
        if (!$container->has($id)) {
            smoke_fail($label, "container has no {$id}");
            continue;
        }
        $container->get($id);
        smoke_ok($label);
    } catch (Throwable $e) {
        smoke_fail($label, $e->getMessage());
    }
}

try {
    /** @var Router $router */
    $router = $container->get(Router::class);
    $named = $router->getNamedRoute('home');
    smoke_ok('route home (' . $named->getPath() . ')');
} catch (Throwable $e) {
    smoke_fail('route home', $e->getMessage());
}

if ($failed) {
    fwrite(STDERR, "\nBoot smoke failed.\n");
    exit(1);
}

fwrite(STDOUT, "\nBoot smoke OK.\n");
exit(0);
