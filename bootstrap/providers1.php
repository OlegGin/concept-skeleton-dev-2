<?php declare(strict_types=1);

use Concept\App\Foundation\PathName;
use Concept\App\Providers\ApplicationComponentsServiceProvider;
use Concept\App\Providers\ApplicationRuntimeServiceProvider;
use Concept\App\Providers\ApplicationServiceProvider;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * @param string $root
 * @return list<callable(): ServiceProviderInterface>
 */
return function(string $root): array {
    $pathMap = [
        PathName::BOOTSTRAP => 'bootstrap',
        PathName::SRC => 'src',
        PathName::CONFIG => 'config',
        PathName::DATABASE => 'database',
        PathName::MIGRATIONS => 'database/migrations',
        PathName::SEEDERS => 'database/seeders',
        PathName::PUBLIC => 'public',
        PathName::STORAGE => 'storage',
        PathName::LOGS => 'storage/logs',
        PathName::CACHE => 'storage/cache',
        PathName::RESOURCES => 'resources',
        PathName::LANG => 'resources/lang',
        PathName::VALIDATOR_TRANSLATIONS => 'resources/lang/validator',
        PathName::VIEWS => 'resources/views',
        PathName::ERRORS_FALLBACK_VIEWS => 'resources/views/errors/fallback',
    ];

    return [
        fn() => new ApplicationServiceProvider($root, $pathMap),
        fn() => new ApplicationRuntimeServiceProvider(),
        fn() => new ApplicationComponentsServiceProvider(),
    ];
};
