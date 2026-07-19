<?php declare(strict_types=1);

namespace Concept\App\Bootstrap;

use Concept\App\Foundation\ConfigKey;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ApplicationRuntimeBootstrap extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string DEFAULT_TIMEZONE = 'UTC';

    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        /** @var ConfigInterface $config */
        $config = $this->getContainer()->get(ConfigInterface::class);

        date_default_timezone_set($config->getString(ConfigKey::APP_TIMEZONE, self::DEFAULT_TIMEZONE));
    }
}
