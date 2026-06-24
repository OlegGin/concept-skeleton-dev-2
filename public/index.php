<?php declare(strict_types=1);

use Concept\App\Http\LoginFormResponse;
use Concept\Core\App;
use Concept\Extensions\Validation\Exceptions\ValidationException;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

try {
    $response = $app->handle();
} catch (ValidationException $e) {
    $response = LoginFormResponse::create($e->getErrors(), $e->getOldData());
}

(new SapiEmitter)->emit($response);
