<?php declare(strict_types=1);

use Concept\Core\App;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

$skeletonRoot = dirname(__DIR__);
require_once $skeletonRoot . '/vendor/autoload.php';

$testAppsDir = $skeletonRoot . '/testApp';
$appName = isset($_GET['app']) && is_string($_GET['app']) ? $_GET['app'] : '';

if ($appName === '') {
    renderTestAppList($testAppsDir);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $appName)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid app name.';
    exit;
}

$bootstrap = $testAppsDir . '/' . $appName . '/bootstrap/app.php';

if (!is_file($bootstrap)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Test app not found.';
    exit;
}

applyTestAppRequestPath();

/** @var App $app */
$app = require $bootstrap;

$response = $app->handle();

(new SapiEmitter())->emit($response);

/**
 * @param string $testAppsDir
 */
function renderTestAppList(string $testAppsDir): void
{
    $apps = discoverTestApps($testAppsDir);
    $self = basename(__FILE__);

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test apps</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; line-height: 1.5; }
        h1 { font-size: 1.25rem; margin-bottom: 1rem; }
        ul { padding-left: 1.25rem; }
        li { margin: 0.35rem 0; }
        a { color: #0b5fff; }
        .empty { color: #666; }
    </style>
</head>
<body>
    <h1>Test apps</h1>
    <?php if ($apps === []): ?>
        <p class="empty">Немає додатків з <code>bootstrap/app.php</code> у <code>testApp/</code>.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($apps as $name): ?>
                <li>
                    <a href="<?= htmlspecialchars($self, ENT_QUOTES, 'UTF-8') ?>?app=<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php if ($name === 'jsonApp'): ?>
                        — <a href="<?= htmlspecialchars($self, ENT_QUOTES, 'UTF-8') ?>?app=jsonApp&amp;path=/api/ping">/api/ping</a>
                        · <code>testApp/jsonApp/public/index.php</code>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
    <?php
}

/**
 * @return list<string>
 */
function discoverTestApps(string $testAppsDir): array
{
    if (!is_dir($testAppsDir)) {
        return [];
    }

    $apps = [];

    foreach (scandir($testAppsDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $testAppsDir . '/' . $entry;

        if (!is_dir($path)) {
            continue;
        }

        if (!is_file($path . '/bootstrap/app.php')) {
            continue;
        }

        $apps[] = $entry;
    }

    sort($apps);

    return $apps;
}

function applyTestAppRequestPath(): void
{
    if (!isset($_GET['path']) || !is_string($_GET['path'])) {
        return;
    }

    $path = $_GET['path'];
    if ($path === '' || !str_starts_with($path, '/')) {
        return;
    }

    $_SERVER['REQUEST_URI'] = $path;
}
