<?php declare(strict_types=1);

use Concept\App\Providers\ApplicationServiceProvider;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * @param string $root
 * @return list<callable(): ServiceProviderInterface>
 */
return function(string $root): array {
    return [
        fn() => new ApplicationServiceProvider($root),
    ];
};
