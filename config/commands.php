<?php declare(strict_types=1);

use Concept\Extensions\DatabaseEloquent\Commands\DbMigrateCommand;
use Concept\Extensions\DatabaseEloquent\Commands\DbMigrationListCommand;
use Concept\Extensions\DatabaseEloquent\Commands\DbRollbackCommand;
use Concept\Extensions\DatabaseEloquent\Commands\DbSeedCommand;
use Concept\Extensions\DatabaseEloquent\Commands\DbSeedersListCommand;
use Concept\Extensions\Http\Commands\RouteListCommand;
use Concept\Extensions\ViewTwig\Commands\ViewClearCommand;

return [
    'commands' => [
        DbMigrateCommand::class,
        DbMigrationListCommand::class,
        DbRollbackCommand::class,
        DbSeedCommand::class,
        DbSeedersListCommand::class,
        RouteListCommand::class,
        ViewClearCommand::class,
    ],
];
