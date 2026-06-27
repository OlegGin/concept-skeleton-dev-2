#!/usr/bin/env php
<?php declare(strict_types=1);

use Concept\Core\App;
use Concept\Extensions\DatabaseEloquent\Registries\MigrationRegistry;
use Illuminate\Database\Migrations\Migrator;

$root = dirname(__DIR__);

require dirname($root, 3) . '/vendor/autoload.php';

/** @var App $app */
$app = require $root . '/bootstrap/app.php';
$container = $app->getContainer();

/** @var Migrator $migrator */
$migrator = $container->get(Migrator::class);
/** @var MigrationRegistry $migrationRegistry */
$migrationRegistry = $container->get(MigrationRegistry::class);

if (!$migrator->repositoryExists()) {
    $migrator->getRepository()->createRepository();
}

/** @var list<string> $paths */
$paths = $migrationRegistry->all();
$executed = $migrator->run($paths);

if ($executed === []) {
    echo "Nothing to migrate.\n";
    exit(0);
}

foreach ($executed as $migration) {
    echo "Migrated: {$migration}\n";
}
