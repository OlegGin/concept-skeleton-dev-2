<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationServiceProvider;
use Concept\Core\App;

require __DIR__ . '/env.php';

$app = App::create();
$app->registerServiceProviders([
    fn () => new ApplicationServiceProvider(dirname(__DIR__)),
]);

return $app;
