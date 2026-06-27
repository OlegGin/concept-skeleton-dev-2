<?php declare(strict_types=1);

namespace Concept\Extensions\Config;

use Concept\Extensions\Config\Contracts\ConfigInterface;
use Dotenv\Dotenv;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Noodlehaus\Config as NhConfig;

final class ConfigServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string APP_ENV_KEY = 'APP_ENV';
    private const string DEFAULT_CONFIG_DIR = 'config';

    public function __construct(
        private readonly string $root,
        private readonly string $configDir = self::DEFAULT_CONFIG_DIR,
    ) {}

    public function provides(string $id): bool
    {
        return $id === ConfigInterface::class;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $container = $this->getContainer();

        $nhConfig = new NhConfig($this->rootPath($this->configDir));
        $envData = $this->loadDotEnv($this->root);
        $this->loadOverrideConfig($nhConfig, $envData);
        $this->mergeEnvData($nhConfig, $envData);

        $config = new Config($nhConfig);

        $container->add(ConfigInterface::class, $config)->setShared(true);
    }

    private function rootPath(string $path = ''): string
    {
        if ($path === '') {
            return $this->root;
        }

        return $this->root . '/' . ltrim($path, '/');
    }

    /**
     * @return array<string, string|null>
     */
    private function loadDotEnv(string $rootPath): array
    {
        $dotenv = Dotenv::createImmutable($rootPath);

        return $dotenv->load();
    }

    /**
     * @param array<string, string|null> $envData
     */
    private function loadOverrideConfig(NhConfig $nhConfig, array $envData): void
    {
        $env = $envData[self::APP_ENV_KEY] ?? '';
        $overrideConfigPath = $this->rootPath($this->configDir . '/' . $env);
        if (is_dir($overrideConfigPath)) {
            $overrideConfig = new NhConfig($overrideConfigPath);
            $nhConfig->merge($overrideConfig);
        }
    }

    /**
     * @param array<string, mixed> $envData
     */
    private function mergeEnvData(NhConfig $nhConfig, array $envData): void
    {
        foreach ($envData as $key => $value) {
            $parts = explode('_', strtolower($key), 2);
            $root = $parts[0];
            $sub = $parts[1] ?? '';

            $configKey = empty($sub) ? $root : sprintf('%s.%s', $root, $sub);
            $nhConfig->set($configKey, $value);
        }
    }
}
