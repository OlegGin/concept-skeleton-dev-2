<?php declare(strict_types=1);

use Concept\App\Foundation\AppProfile;
use Concept\App\Http\Error\Handlers\FallbackFileHandler;
use Concept\App\Http\Error\Handlers\ReportExceptionHandler;
use Concept\App\Http\Error\PhpErrorLogWriter;
use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsServiceProvider;
use League\Container\Container;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
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

$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$errorsFallbackFilePath = $root . '/resources/views/errors/fallback/500.php';

$earlyRenderHandler = match (true) {
    $debug => new PrettyPageHandler(),
    PHP_SAPI === 'cli' => new PlainTextHandler(),
    default => new FallbackFileHandler($errorsFallbackFilePath),
};

$container->add(Whoops::class, EarlyWhoopsServiceProvider::register(
    $earlyRenderHandler,
    new ReportExceptionHandler(static fn() => new PhpErrorLogWriter()),
))->setShared(true);

/** @var callable(string): list<ServiceProviderInterface> $providersFactory */
$providersFactory = require $profileProvidersFile;
$app->registerServiceProviders($providersFactory($root));

return $app;
