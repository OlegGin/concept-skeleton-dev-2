<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\View\Twig\AppExtension;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\CastingValinor\CastingServiceProvider;
use Concept\Extensions\CastingValinor\Routing\TypedRouteParameterArgumentResolver;
use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\Extensions\Config\ConfigServiceProvider;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Config\Foundation\PathManager;
use Concept\Extensions\ConsoleSymfony\ConsoleSymfonyServiceProvider;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\DatabaseEloquent\DatabaseEloquentServiceProvider;
use Concept\Extensions\DatabaseEloquent\PaginationConfiguratorServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\Extensions\SessionSymfony\SessionServiceProvider;
use Concept\Extensions\ValidationRakit\ValidationServiceProvider;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Symfony\Component\Console\Command\Command;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function __construct(private readonly string $root) {}

    public function provides(string $id): bool
    {
        return false;
    }

    public function boot(): void
    {
        $this->registerConfigProvider();

        $container = $this->getContainer();
        $this->registerCastingProvider();
        $this->registerDataMaskerProvider();
        $this->registerLoggerProvider();
        $this->registerDatabaseProvider();
        $container->addServiceProvider(new ValidationServiceProvider());
        $container->addServiceProvider(new FormRequestServiceProvider());
        $this->registerSessionProvider();
        $container->addServiceProvider(new CsrfServiceProvider());
        $this->registerRoutingProvider();
        $container->addServiceProvider(new PaginationConfiguratorServiceProvider());
        $container->addServiceProvider(new HttpServiceProvider());
        $this->registerViewProvider();
        $this->registerTwigViewProvider();
        $this->registerConsoleProvider();
        $this->registerErrorHandlerProvider();
    }

    public function register(): void
    {
    }

    private function registerConfigProvider(): void
    {
        /** @var array<string, string> $paths */
        $paths = require $this->root . '/bootstrap/paths.php';

        $this->getContainer()->addServiceProvider(new ConfigServiceProvider(
            root: $this->root,
            configDir: $paths[PathName::CONFIG] ?? 'config',
            pathMap: $paths,
        ));
    }

    private function config(): ConfigInterface
    {
        /** @var ConfigInterface $config */
        $config = $this->getContainer()->get(ConfigInterface::class);

        return $config;
    }

    private function paths(): PathManager
    {
        /** @var PathManager $paths */
        $paths = $this->getContainer()->get(PathManager::class);

        return $paths;
    }

    private function registerCastingProvider(): void
    {
        $config = $this->config();
        $paths = $this->paths();

        /** @var list<class-string> $transformerClasses */
        $transformerClasses = $config->get(ConfigKey::CASTER_TRANSFORMERS) ?? [];

        $this->getContainer()->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $paths->get(PathName::CACHE, 'valinor'),
            transformerClasses: $transformerClasses,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
        ));
    }

    private function registerDataMaskerProvider(): void
    {
        $config = $this->config();

        /** @var array<string, string> $patterns */
        $patterns = $config->get(ConfigKey::MASKING_PATTERNS) ?? [];

        /** @var list<string> $keyPatterns */
        $keyPatterns = $config->get(ConfigKey::MASKING_KEY_PATTERNS) ?? [];

        /** @var list<class-string<DataMaskerRuleInterface>> $rules */
        $rules = $config->get(ConfigKey::MASKING_RULES) ?? [];

        $this->getContainer()->addServiceProvider(new DataMaskerServiceProvider(
            patterns: $patterns,
            keyPatterns: $keyPatterns,
            rules: $rules,
        ));
    }

    private function registerLoggerProvider(): void
    {
        $config = $this->config();
        $paths = $this->paths();

        $this->getContainer()->addServiceProvider(new LoggerMonologServiceProvider(
            path: $paths->get(PathName::LOGS, 'app.log'),
            level: $config->getString(ConfigKey::LOG_LEVEL),
            maxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            channel: $config->getString(ConfigKey::LOG_NAME),
        ));
    }

    private function registerDatabaseProvider(): void
    {
        $config = $this->config();
        $paths = $this->paths();

        /** @var list<string> $migrationPathKeys */
        $migrationPathKeys = $config->get(ConfigKey::MIGRATIONS_PATHS) ?? [];

        /** @var list<class-string> $seeders */
        $seeders = $config->get(ConfigKey::SEEDERS_LIST) ?? [];

        $this->getContainer()->addServiceProvider(new DatabaseEloquentServiceProvider(
            connection: [
                'driver' => $config->getString(ConfigKey::DB_DRIVER),
                'host' => $config->getString(ConfigKey::DB_HOST),
                'database' => $config->getString(ConfigKey::DB_DATABASE),
                'username' => $config->getString(ConfigKey::DB_USERNAME),
                'password' => $config->getString(ConfigKey::DB_PASSWORD),
                'charset' => $config->getString(ConfigKey::DB_CHARSET),
                'collation' => $config->getString(ConfigKey::DB_COLLATION),
                'prefix' => $config->getString(ConfigKey::DB_PREFIX),
            ],
            logDbQueries: $config->getBool(ConfigKey::LOG_DB_QUERIES),
            queryLogPath: $paths->get(PathName::LOGS, 'query.log'),
            logMaxFiles: $config->getInt(ConfigKey::LOG_MAX_FILES),
            migrationsTable: $config->getString(ConfigKey::MIGRATIONS_TABLE, 'migrations'),
            migrationPaths: array_map(
                fn (string $path): string => $paths->root($path),
                $migrationPathKeys,
            ),
            seeders: $seeders,
        ));
    }

    private function registerConsoleProvider(): void
    {
        $config = $this->config();

        /** @var list<class-string<Command>> $commands */
        $commands = $config->get(ConfigKey::COMMANDS) ?? [];

        $this->getContainer()->addServiceProvider(new ConsoleSymfonyServiceProvider(
            appName: $config->getString(ConfigKey::APP_NAME),
            appVersion: $config->getString(ConfigKey::APP_VERSION),
            commands: $commands,
        ));
    }

    private function registerSessionProvider(): void
    {
        $paths = $this->paths();

        $this->getContainer()->addServiceProvider(new SessionServiceProvider(
            savePath: $paths->get(PathName::STORAGE, 'sessions'),
        ));
    }

    private function registerRoutingProvider(): void
    {
        $config = $this->config();
        $paths = $this->paths();
        $container = $this->getContainer();

        /** @var list<string> $routePathKeys */
        $routePathKeys = $config->get(ConfigKey::ROUTES_LIST) ?? [];

        $container->addServiceProvider(new CoreHttpServiceProvider(
            routePaths: array_map(
                fn (string $path): string => $paths->root($path),
                $routePathKeys,
            ),
            resolvers: [
                new FormRequestArgumentResolver($container),
                new ServerRequestArgumentResolver(),
                new TypedRouteParameterArgumentResolver($container),
                new RouteParameterArgumentResolver(),
            ],
        ));
    }

    private function registerViewProvider(): void
    {
        $config = $this->config();
        $container = $this->getContainer();

        $container->add(AppExtension::class, fn (): AppExtension => new AppExtension(
            $this->config()->getString(ConfigKey::APP_NAME),
        ))->setShared(true);

        /** @var array<string, string> $viewPaths */
        $viewPaths = $config->get(ConfigKey::VIEW_PATHS) ?? [];

        /** @var list<class-string> $viewExtensions */
        $viewExtensions = $config->get(ConfigKey::VIEW_EXTENSIONS) ?? [];

        $container->addServiceProvider(new ViewServiceProvider(
            paths: $viewPaths,
            extensions: $viewExtensions,
        ));
    }

    private function registerTwigViewProvider(): void
    {
        $config = $this->config();
        $paths = $this->paths();

        $this->getContainer()->addServiceProvider(new TwigViewServiceProvider(
            root: $this->root,
            viewsPath: $paths->get(PathName::VIEWS),
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            cacheDir: $paths->get(PathName::CACHE, 'views'),
        ));
    }

    private function registerErrorHandlerProvider(): void
    {
        $config = $this->config();
        $paths = $this->paths();

        $this->getContainer()->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            root: $this->root,
            debug: $config->getBool(ConfigKey::APP_DEBUG),
            errorsFallbackPath: $paths->get(PathName::ERRORS_FALLBACK_VIEWS),
        ));
    }
}
