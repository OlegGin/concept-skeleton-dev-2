<?php declare(strict_types=1);

use Concept\App\Http\Error\Handlers\FallbackFileHandler;
use Concept\App\Providers\ApplicationComponentsServiceProvider;
use Concept\App\Providers\ApplicationRuntimeServiceProvider;
use Concept\App\Providers\Layers\FoundationLayerProvider;
use Concept\Core\App;
use Concept\Extensions\ErrorHandlerWhoops\EarlyWhoopsServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\Handlers\ReportExceptionHandler;
use Concept\Stack\Bricks\ErrorHandling\Reporting\PhpErrorLogReporter;
use League\Container\Container;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
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
    new ReportExceptionHandler(static fn() => new PhpErrorLogReporter()),
))->setShared(true);

/** @var callable(ContainerInterface): list<ServiceProviderInterface> $providersFactory */
$providersFactory = require __DIR__ . '/providers.php';

/** @var array<string, string> $pathMap */
$pathMap = require __DIR__ . '/path-map.php';
$app->registerServiceProviders([new FoundationLayerProvider($root, $pathMap)]);
$app->registerServiceProviders($providersFactory($container));
$app->registerServiceProviders([
    new ApplicationComponentsServiceProvider(),
    new ApplicationRuntimeServiceProvider(),
]);

return $app;
