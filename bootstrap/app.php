<?php declare(strict_types=1);

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

$root = dirname(__DIR__);

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
$providersFactory = require __DIR__ . '/providers.php';
$app->registerServiceProviders($providersFactory($root));

return $app;
