<?php declare(strict_types=1);

use Concept\App\Foundation\PathName;
use Concept\App\Providers\ApplicationRuntimeServiceProvider;
use Concept\App\Providers\ApplicationServiceProvider;
use Concept\Extensions\Config\ConfigServiceProvider;

/**
 * @param string $root
 * @param array<string, string> $paths
 * @return list<callable(): \League\Container\ServiceProvider\ServiceProviderInterface>
 */
return function(string $root, array $paths): array {
    return [
        fn() => new ConfigServiceProvider(
            root: $root,
            configDir: $paths[PathName::CONFIG] ?? 'config',
            pathMap: $paths,
        ),
        fn() => new ApplicationRuntimeServiceProvider(),
        fn() => new ApplicationServiceProvider($root),
    ];
};
