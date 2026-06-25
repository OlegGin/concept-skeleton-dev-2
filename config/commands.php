<?php declare(strict_types=1);

use Concept\Extensions\ConsoleSymfony\Commands\RouteListCommand;
use Concept\Extensions\ViewTwig\Commands\ViewClearCommand;

return [
    'commands' => [
        RouteListCommand::class,
        ViewClearCommand::class,
    ],
];
