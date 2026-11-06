<?php declare(strict_types=1);

use Concept\Core\App;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$request = $app->requestFromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$response = $app->handle($request);

(new SapiEmitter())->emit($response);
