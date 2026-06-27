<?php declare(strict_types=1);

use Concept\Core\App;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

const TEST_APP_SCRIPT = 'testApp.php';

$skeletonRoot = dirname(__DIR__);
require_once $skeletonRoot . '/vendor/autoload.php';

$testAppsDir = $skeletonRoot . '/src/testApp';

$route = resolveTestAppRoute();

if ($route === null) {
    renderTestAppList($testAppsDir);
    exit;
}

$appName = $route['app'];
$requestPath = $route['path'];

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $appName)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid app name.';
    exit;
}

$resolvedAppName = resolveTestAppName($testAppsDir, $appName);

if ($resolvedAppName === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Test app not found.';
    exit;
}

$bootstrap = $testAppsDir . '/' . $resolvedAppName . '/bootstrap/app.php';

applyTestAppRequestPath($requestPath);

/** @var App $app */
$app = require $bootstrap;

$response = $app->handle();

(new SapiEmitter())->emit($response);

/**
 * @return array{app: string, path: string}|null
 */
function resolveTestAppRoute(): ?array
{
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (is_string($pathInfo) && $pathInfo !== '') {
        $parsed = parseTestAppPathInfo($pathInfo);
        if ($parsed !== null) {
            return $parsed;
        }
    }

    $requestUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/' . TEST_APP_SCRIPT;

    if (is_string($requestUriPath) && str_starts_with($requestUriPath, $scriptName . '/')) {
        $parsed = parseTestAppPathInfo(substr($requestUriPath, strlen($scriptName)));
        if ($parsed !== null) {
            return $parsed;
        }
    }

    if (isset($_GET['app']) && is_string($_GET['app']) && $_GET['app'] !== '') {
        $path = '/';
        if (isset($_GET['path']) && is_string($_GET['path']) && str_starts_with($_GET['path'], '/')) {
            $path = $_GET['path'];
        }

        return ['app' => $_GET['app'], 'path' => $path];
    }

    return null;
}

/**
 * @return array{app: string, path: string}|null
 */
function parseTestAppPathInfo(string $pathInfo): ?array
{
    $trimmed = trim($pathInfo, '/');
    if ($trimmed === '') {
        return null;
    }

    $segments = explode('/', $trimmed);
    $appName = array_shift($segments);

    if ($appName === null || $appName === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $appName)) {
        return null;
    }

    $requestPath = $segments === [] ? '/' : '/' . implode('/', $segments);

    return ['app' => $appName, 'path' => $requestPath];
}

function resolveTestAppName(string $testAppsDir, string $appName): ?string
{
    if (is_file($testAppsDir . '/' . $appName . '/bootstrap/app.php')) {
        return $appName;
    }

    foreach (discoverTestApps($testAppsDir) as $discovered) {
        if (strcasecmp($discovered, $appName) === 0) {
            return $discovered;
        }
    }

    return null;
}

/**
 * @param string $testAppsDir
 */
function renderTestAppList(string $testAppsDir): void
{
    $apps = discoverTestApps($testAppsDir);
    $base = TEST_APP_SCRIPT;

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
        code { font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Test apps</h1>
    <?php if ($apps === []): ?>
        <p class="empty">Немає додатків з <code>bootstrap/app.php</code> у <code>src/testApp/</code>.</p>
    <?php else: ?>
        <p>Формат URL: <code><?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/{AppName}/…</code></p>
        <ul>
            <?php foreach ($apps as $name): ?>
                <li>
                    <a href="<?= htmlspecialchars($base . '/' . $name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php if ($name === 'jsonApp'): ?>
                        — <a href="<?= htmlspecialchars($base . '/' . $name . '/api/ping', ENT_QUOTES, 'UTF-8') ?>">/api/ping</a>
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

function applyTestAppRequestPath(string $path): void
{
    if ($path === '' || !str_starts_with($path, '/')) {
        $path = '/';
    }

    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $_SERVER['REQUEST_URI'] = $path . ($queryString !== '' ? '?' . $queryString : '');
}
