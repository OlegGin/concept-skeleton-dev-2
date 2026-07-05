<?php declare(strict_types=1);

use Concept\App\Providers\Profiles\Minimal\MinimalHttpServiceProvider;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * @param string $root
 * @return list<ServiceProviderInterface>
 */
return function(string $root): array {
    return [
        new MinimalHttpServiceProvider($root),
    ];
};
