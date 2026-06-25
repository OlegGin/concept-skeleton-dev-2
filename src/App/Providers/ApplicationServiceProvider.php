<?php declare(strict_types=1);

namespace Concept\App\Providers;

use Concept\App\View\Twig\AppExtension;
use Concept\Core\Http\Routing\Resolvers\RouteParameterArgumentResolver;
use Concept\Core\Http\Routing\Resolvers\ServerRequestArgumentResolver;
use Concept\Core\Providers\Http\HttpServiceProvider as CoreHttpServiceProvider;
use Concept\Extensions\CastingValinor\CastingServiceProvider;
use Concept\Extensions\ConsoleSymfony\ConsoleSymfonyServiceProvider;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\DataMasker\DataMaskerServiceProvider;
use Concept\Extensions\DatabaseEloquent\DatabaseEloquentServiceProvider;
use Concept\Extensions\DatabaseEloquent\PaginationConfiguratorServiceProvider;
use Concept\Extensions\ErrorHandlerWhoops\ErrorHandlerWhoopsServiceProvider;
use Concept\Extensions\LoggerMonolog\LoggerMonologServiceProvider;
use Concept\Extensions\CastingValinor\Routing\TypedRouteParameterArgumentResolver;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\Http\HttpServiceProvider;
use Concept\Extensions\SessionSymfony\SessionServiceProvider;
use Concept\Extensions\ValidationRakit\ValidationServiceProvider;
use Concept\Extensions\View\ViewServiceProvider;
use Concept\Extensions\ViewTwig\TwigViewServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ApplicationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function __construct(private readonly string $root) {}

    public function provides(string $id): bool
    {
        return false;
    }

    public function boot(): void
    {
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

    private function registerCastingProvider(): void
    {
        $this->getContainer()->addServiceProvider(new CastingServiceProvider(
            cacheDirectory: $this->root . '/storage/cache/valinor',
            debug: $this->appDebug(),
        ));
    }

    private function registerDataMaskerProvider(): void
    {
        /** @var array{
         *     masking: array{
         *         patterns: array<string, string>,
         *         key_patterns: list<string>,
         *         rules: list<class-string<\Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface>>
         *     }
         * } $config
         */
        $config = require $this->root . '/config/masking.php';
        $masking = $config['masking'];

        $this->getContainer()->addServiceProvider(new DataMaskerServiceProvider(
            patterns: $masking['patterns'],
            keyPatterns: $masking['key_patterns'],
            rules: $masking['rules'],
        ));
    }

    private function registerLoggerProvider(): void
    {
        /** @var array{
         *     logging: array{
         *         name: string,
         *         level: string,
         *         max_files: int,
         *     }
         * } $config
         */
        $config = require $this->root . '/config/logging.php';
        $logging = $config['logging'];

        $this->getContainer()->addServiceProvider(new LoggerMonologServiceProvider(
            path: $this->root . '/storage/logs/app.log',
            level: is_string($_ENV['LOG_LEVEL'] ?? null) ? $_ENV['LOG_LEVEL'] : $logging['level'],
            maxFiles: filter_var($_ENV['LOG_MAX_FILES'] ?? $logging['max_files'], FILTER_VALIDATE_INT) ?: $logging['max_files'],
            channel: $logging['name'],
        ));
    }

    private function registerDatabaseProvider(): void
    {
        /** @var array{
         *     database: array{
         *         driver: string,
         *         host: string,
         *         database: string,
         *         username: string,
         *         password: string,
         *         charset: string,
         *         collation: string,
         *         prefix: string,
         *     }
         * } $config
         */
        $config = require $this->root . '/config/database.php';
        $database = $config['database'];

        /** @var array{logging: array{max_files: int, db_queries: bool}} $loggingConfig */
        $loggingConfig = require $this->root . '/config/logging.php';
        $logging = $loggingConfig['logging'];

        /** @var array{migrations: array{table: string, paths: list<string>}} $migrationsConfig */
        $migrationsConfig = require $this->root . '/config/migrations.php';
        $migrations = $migrationsConfig['migrations'];

        /** @var array{seeders: array{list: list<class-string>}} $seedersConfig */
        $seedersConfig = require $this->root . '/config/seeders.php';
        $seeders = $seedersConfig['seeders'];

        $container = $this->getContainer();

        $container->addServiceProvider(new DatabaseEloquentServiceProvider(
            connection: [
                'driver' => is_string($_ENV['DB_DRIVER'] ?? null) ? $_ENV['DB_DRIVER'] : $database['driver'],
                'host' => is_string($_ENV['DB_HOST'] ?? null) ? $_ENV['DB_HOST'] : $database['host'],
                'database' => is_string($_ENV['DB_DATABASE'] ?? null) ? $_ENV['DB_DATABASE'] : $database['database'],
                'username' => is_string($_ENV['DB_USERNAME'] ?? null) ? $_ENV['DB_USERNAME'] : $database['username'],
                'password' => is_string($_ENV['DB_PASSWORD'] ?? null) ? $_ENV['DB_PASSWORD'] : $database['password'],
                'charset' => $database['charset'],
                'collation' => $database['collation'],
                'prefix' => $database['prefix'],
            ],
            logDbQueries: filter_var($_ENV['LOG_DB_QUERIES'] ?? $logging['db_queries'], FILTER_VALIDATE_BOOL),
            queryLogPath: $this->root . '/storage/logs/query.log',
            logMaxFiles: filter_var($_ENV['LOG_MAX_FILES'] ?? $logging['max_files'], FILTER_VALIDATE_INT) ?: $logging['max_files'],
            migrationsTable: $migrations['table'],
            migrationPaths: array_map(
                fn (string $path): string => $this->root . '/' . ltrim($path, '/'),
                $migrations['paths'],
            ),
            seeders: $seeders['list'],
        ));
    }

    private function registerConsoleProvider(): void
    {
        /** @var array{commands: list<class-string<\Symfony\Component\Console\Command\Command>>} $config */
        $config = require $this->root . '/config/commands.php';

        $appName = $_ENV['APP_NAME'] ?? 'Concept';
        $appVersion = $_ENV['APP_VERSION'] ?? '1.0.0';

        $this->getContainer()->addServiceProvider(new ConsoleSymfonyServiceProvider(
            appName: is_string($appName) ? $appName : 'Concept',
            appVersion: is_string($appVersion) ? $appVersion : '1.0.0',
            commands: $config['commands'],
        ));
    }

    private function registerSessionProvider(): void
    {
        $this->getContainer()->addServiceProvider(new SessionServiceProvider(
            savePath: $this->root . '/storage/sessions',
        ));
    }

    private function registerRoutingProvider(): void
    {
        $container = $this->getContainer();
        $container->addServiceProvider(new CoreHttpServiceProvider(
            routePaths: [$this->root . '/routes/web.php', $this->root . '/routes/api.php'],
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
        $container = $this->getContainer();
        $container->add(AppExtension::class, fn (): AppExtension => new AppExtension(
            $this->appName(),
        ))->setShared(true);

        $container->addServiceProvider(new ViewServiceProvider(
            paths: [
                'app' => '/resources/views',
            ],
            extensions: [
                AppExtension::class,
            ],
        ));
    }

    private function registerTwigViewProvider(): void
    {
        $this->getContainer()->addServiceProvider(new TwigViewServiceProvider(
            root: $this->root,
            viewsPath: $this->root . '/resources/views',
            debug: $this->appDebug(),
            cacheDir: $this->root . '/storage/cache/views',
        ));
    }

    private function registerErrorHandlerProvider(): void
    {
        $this->getContainer()->addServiceProvider(new ErrorHandlerWhoopsServiceProvider(
            root: $this->root,
            debug: $this->appDebug(),
            errorsFallbackPath: $this->root . '/resources/views/errors/fallback',
        ));
    }

    private function appDebug(): bool
    {
        return filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
    }

    private function appName(): string
    {
        $name = $_ENV['APP_NAME'] ?? 'Concept';

        return is_string($name) ? $name : 'Concept';
    }
}
