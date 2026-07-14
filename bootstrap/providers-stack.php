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
    $env = static function(string $key, string $default): string {
        $value = getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    };

    // Docker Compose service name is `db` (MYSQL_HOST). container_name is not a reliable DNS name.
    $dbHost = $env('DB_HOST', $env('MYSQL_HOST', 'db'));

    return ConceptStack::create()
        ->withMasking()
            ->keyPatterns(['/.*password.*/i', '/.*token.*/i'])
            ->end()
        ->withLogging()
            ->level('debug')
            ->channel('stack')
            ->toRotatingFile($root . '/storage/logs/stack.log')
            ->withMasking()
            ->end()
        ->withDatabase()
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
            ->withMasking()
            ->end()
        ->withCasting()
            ->cacheDir($root . '/storage/cache/casting')
            ->debug(true)
            ->end()
        ->withValidation()
            ->globalExcept(['password'])
            ->end()
        ->withSession()
            ->options([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_only_cookies' => true,
                'use_strict_mode' => true,
            ])
            ->withCsrf()
            ->end()
        ->withHttp()
            ->routes([$root . '/routes/stack.php'])
            ->withFormRequests()
            ->withTypedRouteParameters()
            ->end()
        ->withConsole()
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
            ])
            ->end()
        ->providers();
};
