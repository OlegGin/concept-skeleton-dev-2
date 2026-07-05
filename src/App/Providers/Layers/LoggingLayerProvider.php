<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Providers\Support\DataMaskerFactory;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class LoggingLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $container = $this->getContainer();

        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);

        /** @var array<string, string> $patterns */
        $patterns = $config->getArray(ConfigKey::MASKING_PATTERNS);
        /** @var list<string> $keyPatterns */
        $keyPatterns = $config->getArray(ConfigKey::MASKING_KEY_PATTERNS);
        /** @var list<class-string<DataMaskerRuleInterface>> $rules */
        $rules = $config->getArray(ConfigKey::MASKING_RULES);
        $container->addServiceProvider(new DataMaskerServiceProvider(
            patterns: $patterns,
            keyPatterns: $keyPatterns,
            rules: $rules,
        ));

        $container->addServiceProvider(new LoggerMonologServiceProvider(
            logFilePath: $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::LOG_FILE)),
            level: $config->getString(ConfigKey::LOG_LEVEL),
            maxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            channel: $config->getString(ConfigKey::LOG_NAME),
            dataMaskerFactory: DataMaskerFactory::fromContainer($container),
        ));
    }
}
