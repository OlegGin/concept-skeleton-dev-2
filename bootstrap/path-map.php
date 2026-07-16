<?php declare(strict_types=1);

use Concept\App\Foundation\PathName;

/**
 * @return array<string, string>
 */
return [
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
];
