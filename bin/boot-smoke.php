<?php declare(strict_types=1);

/**
 * Boot smoke: load application container and resolve core wiring (no DB).
 *
 * Exit 0 on success, 1 on failure.
 */

use Concept\Core\App;
use Concept\Extensions\CacheProbe\CacheProbe;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\PathManager\PathManager;
use League\Route\Router;
use Psr\SimpleCache\CacheInterface;
use Whoops\Run as Whoops;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$failed = false;

function smoke_ok(string $label): void
{
    fwrite(STDOUT, "[OK]   {$label}\n");
}

function smoke_fail(string $label, string $detail): bool
{
    fwrite(STDERR, "[FAIL] {$label} — {$detail}\n");

    return true;
}

try {
    /** @var App $app */
    $app = require $root . '/bootstrap/app.php';
    smoke_ok('bootstrap/app.php');
} catch (Throwable $e) {
    $failed = smoke_fail('bootstrap/app.php', $e->getMessage());
    exit(1);
}

$container = $app->getContainer();

$required = [
    Whoops::class => 'early Whoops',
    PathManager::class => 'PathManager',
    ConfigInterface::class => 'ConfigInterface',
    CacheInterface::class => 'CacheInterface',
    Router::class => 'Router',
];

foreach ($required as $id => $label) {
    try {
        if (!$container->has($id)) {
            $failed = smoke_fail($label, "container has no {$id}");
            continue;
        }
        $container->get($id);
        smoke_ok($label);
    } catch (Throwable $e) {
        $failed = smoke_fail($label, $e->getMessage());
    }
}

try {
    /** @var Router $router */
    $router = $container->get(Router::class);
    $named = $router->getNamedRoute('home');
    smoke_ok('route home (' . $named->getPath() . ')');
} catch (Throwable $e) {
    $failed = smoke_fail('route home', $e->getMessage());
}

try {
    /** @var CacheProbe $probe */
    $probe = $container->get(CacheProbe::class);
    $value = $probe->ping();
    if ($value !== 'ok') {
        $failed = smoke_fail('CacheProbe', "expected ok, got {$value}");
    } else {
        smoke_ok('CacheProbe (requires cache + logging)');
    }
} catch (Throwable $e) {
    $failed = smoke_fail('CacheProbe', $e->getMessage());
}

if ($failed) {
    fwrite(STDERR, "\nBoot smoke failed.\n");
    exit(1);
}

fwrite(STDOUT, "\nBoot smoke OK.\n");
exit(0);
