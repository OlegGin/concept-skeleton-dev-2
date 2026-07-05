<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Providers\Support\ApplicationPaths;
use Concept\App\Providers\Support\DataMaskerFactory;
use Concept\Extensions\Config\Contracts\ConfigInterface;
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

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $paths = new ApplicationPaths($pathManager);

        /** @var array<string, string> $patterns */
        $patterns = $config->get(ConfigKey::MASKING_PATTERNS) ?? [];
        /** @var list<string> $keyPatterns */
        $keyPatterns = $config->get(ConfigKey::MASKING_KEY_PATTERNS) ?? [];
        /** @var list<class-string> $rules */
        $rules = $config->get(ConfigKey::MASKING_RULES) ?? [];
        $container->addServiceProvider(new DataMaskerServiceProvider(
            patterns: $patterns,
            keyPatterns: $keyPatterns,
            rules: $rules,
        ));

        $container->addServiceProvider(new LoggerMonologServiceProvider(
            logFilePath: $paths->logFile($config->getString(ConfigKey::LOG_FILE)),
            level: $config->getString(ConfigKey::LOG_LEVEL),
            maxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            channel: $config->getString(ConfigKey::LOG_NAME),
            dataMaskerFactory: DataMaskerFactory::fromContainer($container),
        ));
    }
}
