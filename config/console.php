<?php declare(strict_types=1);

use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrateCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrationListCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbMigrationPathsCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbRollbackCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbSeedCommand;
use Concept\Extensions\DatabaseEloquent\Console\Commands\DbSeederListCommand;
use Concept\Extensions\Http\Console\Commands\RouteListCommand;
use Concept\Extensions\ViewTwig\Console\Commands\ViewClearCommand;

return [
    'console' => [
        'commands' => [
            DbMigrateCommand::class,
            DbMigrationListCommand::class,
            DbMigrationPathsCommand::class,
            DbRollbackCommand::class,
            DbSeedCommand::class,
            DbSeederListCommand::class,
            RouteListCommand::class,
            ViewClearCommand::class,
        ],
    ],
];
