<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationServiceProvider;
use Concept\Core\App;

$app = App::create();
$app->registerServiceProviders([
    fn () => new ApplicationServiceProvider(dirname(__DIR__)),
]);

return $app;
