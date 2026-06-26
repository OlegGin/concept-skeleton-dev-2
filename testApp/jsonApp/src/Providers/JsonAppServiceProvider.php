<?php declare(strict_types=1);

namespace JsonApp\Providers;

use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\Config\ConfigServiceProvider;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Config\Foundation\PathManager;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use JsonApp\Foundation\ConfigKey;
use JsonApp\Foundation\PathName;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class JsonAppServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string DEFAULT_TIMEZONE = 'UTC';
    private const string LOG_FILE = 'app.log';

    public function __construct(
        private readonly string $root,
        private readonly array $paths,
    ) {}

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

        $container->addServiceProvider(new ConfigServiceProvider(
            root: $this->root,
            configDir: $paths[PathName::CONFIG] ?? 'config',
            pathMap: $this->paths,
        ));

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        date_default_timezone_set($config->getString(ConfigKey::APP_TIMEZONE, self::DEFAULT_TIMEZONE));

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);

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
            path: $pathManager->get(PathName::LOGS, self::LOG_FILE),
            level: $config->getString(ConfigKey::LOG_LEVEL),
            maxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            channel: $config->getString(ConfigKey::LOG_NAME),
        ));

        /** @var list<string> $routesList */
        $routesList = $config->get(ConfigKey::ROUTES_LIST) ?? [];
        $container->addServiceProvider(new CoreHttpServiceProvider(
            routePaths: $this->toAbsolutePaths($pathManager, $routesList),
            resolvers: [
                new ServerRequestArgumentResolver(),
                new RouteParameterArgumentResolver(),
            ],
            interceptors: [],
        ));

        $container->addServiceProvider(new HttpServiceProvider());

        $container->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            root: $this->root,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            errorsFallbackPath: $this->root,
        ));
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function toAbsolutePaths(PathManager $pathManager, array $paths): array
    {
        return array_map(
            fn(string $path): string => $pathManager->root($path),
            $paths,
        );
    }
}
