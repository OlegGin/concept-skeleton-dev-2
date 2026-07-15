<?php declare(strict_types=1);

use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrateCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrationListCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrationPathsCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbRollbackCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbSeedCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbSeederListCommand;
use Concept\Extensions\Http\Console\Commands\RouteListCommand;
use Concept\Stack\ConceptStack;
use Database\Seeders\PageSeeder;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * Concept Stack test profile — no Config, no PathManager, explicit params only.
 *
 * @return list<ServiceProviderInterface>
 */
return function(string $root): array {
    $stack = ConceptStack::create();

    $stack->withMasking()
        ->keyPatterns(['/.*password.*/i', '/.*token.*/i']);

    $stack->withLogging()
        ->level('debug')
        ->channel('stack')
        ->toRotatingFile($root . '/storage/logs/stack.log')
        ->withMasking();

    $stack->withTelemetry()
        ->enabled(true)
        ->logs(true)
        ->eventName('log.recorded');

    $stack->withDatabase()
        ->connection([
            'driver' => 'mysql',
            'host' => 'db',
            'port' => '3306',
            'database' => 'concept_skeleton_dev_db_2',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ])
        ->migrations([$root . '/database/migrations'])
        ->seeders([PageSeeder::class])
        ->withQueryLogging($root . '/storage/logs/query.log')
        ->withQueryTelemetry()
        ->withMasking();

    $stack->withCasting()
        ->cacheDir($root . '/storage/cache/casting')
        ->debug(true);

    $stack->withValidation()
        ->globalExcept(['password']);

    $stack->withSession()
        ->options([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_only_cookies' => true,
            'use_strict_mode' => true,
        ])
        ->withCsrf();

    $stack->withHttp()
        ->routes([$root . '/routes/stack.php'])
        ->withFormRequests()
        ->withTypedRouteParameters();

    $stack->withView()
        ->paths([
            'stack' => $root . '/resources/views/stack',
        ])
        ->withTwig()
        ->viewsPath($root . '/resources/views')
        ->cacheDir($root . '/storage/cache/views')
        ->debug(true);

    $stack->withConsole()
        ->name('Concept Stack Test')
        ->version('1.0.0')
        ->commands([
            RouteListCommand::class,
            DbMigrateCommand::class,
            DbMigrationListCommand::class,
            DbMigrationPathsCommand::class,
            DbRollbackCommand::class,
            DbSeedCommand::class,
            DbSeederListCommand::class,
        ]);

    return $stack->providers();
};
