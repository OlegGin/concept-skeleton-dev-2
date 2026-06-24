<?php declare(strict_types=1);

use Concept\Core\App;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

(new SapiEmitter)->emit($app->handle());
