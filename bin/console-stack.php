#!/usr/bin/env php
<?php declare(strict_types=1);

use Concept\Core\App;
use Symfony\Component\Console\Application as ConsoleApplication;

require __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$app = require __DIR__ . '/../bootstrap/app-stack.php';

/** @var ConsoleApplication $console */
$console = $app->getContainer()->get(ConsoleApplication::class);

exit($console->run());
