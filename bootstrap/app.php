<?php declare(strict_types=1);

use Concept\App\Foundation\AppProfile;
use Concept\App\Http\Error\PhpErrorLogWriter;
use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsBootstrap;
use Concept\Extensions\ErrorHandlerWhoops\Handlers\PhpErrorLogHandler;
use League\Container\Container;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Whoops\Run as Whoops;

const APP_PROFILE = AppProfile::FULL;

$root = dirname(__DIR__);

if (!AppProfile::isValid(APP_PROFILE)) {
    throw new \RuntimeException(sprintf('Unknown application profile: %s', APP_PROFILE));
}

$profileProvidersFile = __DIR__ . '/profiles/' . APP_PROFILE . '/providers.php';
if (!is_file($profileProvidersFile)) {
    throw new \RuntimeException(sprintf('Profile providers file not found: %s', $profileProvidersFile));
}

$app = App::create();
/** @var Container $container */
$container = $app->getContainer();
$phpErrorLogHandler = new PhpErrorLogHandler(new PhpErrorLogWriter());
$errorsFallbackFilePath = $root . '/resources/views/errors/fallback/500.php';
$container->add(Whoops::class, EarlyWhoopsBootstrap::register($errorsFallbackFilePath, $phpErrorLogHandler))->setShared(true);

/** @var callable(string): list<ServiceProviderInterface> $providersFactory */
$providersFactory = require $profileProvidersFile;
$app->registerServiceProviders($providersFactory($root));

return $app;
