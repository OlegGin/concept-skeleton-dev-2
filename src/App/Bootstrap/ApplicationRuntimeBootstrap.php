<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Foundation\ConfigKey;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use League\Container\Container;

final class ApplicationRuntimeBootstrap
{
    private const string DEFAULT_TIMEZONE = 'UTC';

    public function boot(Container $container): void
    {
        $config = ContainerDependency::get($container, ConfigInterface::class);

        date_default_timezone_set($config->getString(ConfigKey::APP_TIMEZONE, self::DEFAULT_TIMEZONE));
    }
}
