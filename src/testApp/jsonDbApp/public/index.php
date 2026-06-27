<?php declare(strict_types=1);

use Concept\Core\App;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

/** @var App $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$response = $app->handle();

(new SapiEmitter())->emit($response);
