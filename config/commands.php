<?php declare(strict_types=1);

use Concept\Extensions\ConsoleSymfony\Commands\DbMigrateCommand;
use Concept\Extensions\ConsoleSymfony\Commands\DbRollbackCommand;
use Concept\Extensions\ConsoleSymfony\Commands\DbSeedCommand;
use Concept\Extensions\ConsoleSymfony\Commands\DbSeedersListCommand;
use Concept\Extensions\ConsoleSymfony\Commands\RouteListCommand;
use Concept\Extensions\DatabaseEloquent\Commands\DbMigrationListCommand;
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