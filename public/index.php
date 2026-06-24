<?php declare(strict_types=1);

use Concept\Core\App;
use Concept\Extensions\Validation\Exceptions\ValidationException;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

try {
    $response = $app->handle();
} catch (ValidationException $e) {
    $container = $app->getContainer();
    /** @var ViewResponseFactoryInterface $viewResponse */
    $viewResponse = $container->get(ViewResponseFactoryInterface::class);

    $response = $viewResponse->create('home', [
        'errors' => $e->getErrors(),
        'old' => $e->getOldData(),
    ]);
}

(new SapiEmitter)->emit($response);
