<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationComponentsServiceProvider;
use Concept\App\Providers\ApplicationRuntimeServiceProvider;
use Concept\App\Providers\Layers\FoundationLayerProvider;
use Concept\App\Telemetry\Subscribers\ComponentsTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\DatabaseTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\ExtensionsTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\FormRequestTelemetrySubscriber;
use Concept\App\Telemetry\Subscribers\TelemetryEventSubscriber;
use Concept\App\Telemetry\Subscribers\ViewTelemetrySubscriber;
use Concept\App\Telemetry\TelemetryEvent;
use Concept\App\Validation\Rules\ExistsRule;
use Concept\App\Validation\Rules\UniqueRule;
use Concept\App\View\Twig\TwigAppExtension;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrateCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrationListCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrationPathsCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbRollbackCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbSeedCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbSeederListCommand;
use Concept\Extensions\Http\Console\Commands\RouteListCommand;
use Concept\Extensions\ViewTwig\Console\Commands\ViewClearCommand;
use Concept\Stack\ConceptStack;
use Database\Seeders\PageSeeder;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

/**
 * @param string $root
 * @return list<ServiceProviderInterface>
 */
return function(string $root): array {
    $debug = true;
    /** @var array<string, string> $pathMap */
    $pathMap = require __DIR__ . '/path-map.php';

    $stack = ConceptStack::create();

    // Logging Layer
    $stack->withMasking()
        ->keyPatterns(['/.*password.*/i', '/.*token.*/i']);

    $stack->withLogging()
        ->level('ERROR')
        ->channel('app')
        ->toRotatingFile($root . '/storage/logs/app.log', 7)
        ->withMasking();

    // Telemetry Layer
    $stack->withTelemetry()
        ->enabled(true)
        ->logs(true)
        ->eventName(TelemetryEvent::LOG_RECORDED)
        ->subscribers([
            ComponentsTelemetrySubscriber::class,
            DatabaseTelemetrySubscriber::class,
            ExtensionsTelemetrySubscriber::class,
            FormRequestTelemetrySubscriber::class,
            ViewTelemetrySubscriber::class,
            TelemetryEventSubscriber::class,
        ]);

    // Validation Layer
    $stack->withValidation()
        ->customRules([
            'exists' => ExistsRule::class,
            'unique' => UniqueRule::class,
        ])
        ->logEnabled(false)
        ->logFilePath($root . '/storage/logs/validation.log')
        ->logMaxFiles(7)
        ->globalExcept([
            '_csrf_token',
        ])
        ->withMasking();

    // Database Layer
    $stack->withDatabase()
        ->connection([
            'driver' => 'mysql',
            'host' => 'concept_skeleton_dev_db_2',
            'port' => 3306,
            'database' => 'concept_skeleton_dev_db_2',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ])
        ->migrations([$root . '/database/migrations'])
        ->migrationsTable('migrations')
        ->seeders([PageSeeder::class])
        ->withQueryLogging($root . '/storage/logs/query.log', 7)
        ->withEmitQueryEvents()
        ->withMasking();

    // Session Layer
    $stack->withSession()
        ->options([
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'cookie_domain' => '',
            'cookie_samesite' => 'Lax',
            'use_only_cookies' => true,
            'use_strict_mode' => true,
        ])
        ->handler(new NativeFileSessionHandler())
        ->withCsrf();

    // Casting Layer
    $stack->withCasting()
        ->transformers([]) // From config/caster.php
        ->cacheDir($root . '/storage/cache/valinor') // From config/caster.php
        ->debug($debug); // From config/app.php

    // Http Layer
    $stack->withHttp()
        ->routes([
            $root . '/routes/web.php',
            $root . '/routes/api.php',
        ]) // From config/routes.php
        ->interceptors([]) // From config/routes.php
        ->withFormRequests()
        ->withTypedRouteParameters();

    // Console Layer
    $stack->withConsole()
        ->name('Concept Skeleton') // From config/app.php
        ->version('1.0.0') // From config/app.php
        ->commands([
            DbMigrateCommand::class,
            DbMigrationListCommand::class,
            DbMigrationPathsCommand::class,
            DbRollbackCommand::class,
            DbSeedCommand::class,
            DbSeederListCommand::class,
            RouteListCommand::class,
            ViewClearCommand::class,
        ]);

    // View Layer
    $stack->withView()
        ->paths([
            'frontend' => $root . '/resources/views/frontend',
            'dashboard' => $root . '/resources/views/dashboard',
        ])
        ->extensions([
            TwigAppExtension::class,
        ])
        ->routeNamespace([])
        ->withTwig()
        ->viewsPath($root . '/resources/views')
        ->cacheDir($root . '/storage/cache/views')
        ->debug($debug); // From config/app.php

    // Error handling — stack recipes (Whoops stays thin; implementations in stack)
    $stack->withErrorHandling()
        ->debug($debug)
        ->showDebugExceptionPage()
        ->reportToLog()
        ->renderHtmlErrorPage($root . '/resources/views/errors/fallback');

    return [
        new FoundationLayerProvider($root, $pathMap),
        ...$stack->providers(),
        new ApplicationComponentsServiceProvider(),
        new ApplicationRuntimeServiceProvider(),
    ];
};
