<?php declare(strict_types=1);

use Concept\Extensions\Http\Console\Commands\RouteListCommand;
use Concept\Stack\ConceptStack;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * Concept Stack test profile — no Config, no PathManager, explicit params only.
 *
 * @return list<ServiceProviderInterface>
 */
return function(string $root): array {
    return ConceptStack::create()
        ->withMasking()
            ->keyPatterns(['/.*password.*/i', '/.*token.*/i'])
            ->end()
        ->withLogging()
            ->file($root . '/storage/logs/stack.log')
            ->level('debug')
            ->channel('stack')
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
            ])
            ->end()
        ->providers();
};
