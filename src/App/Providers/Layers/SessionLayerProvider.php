<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Csrf\CsrfServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use Concept\Extensions\SessionSymfony\SessionServiceProvider;
use InvalidArgumentException;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

final class SessionLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string INCORRECT_SESSION_FILE_PATH = 'Session file path must be a string or null, %s given.';

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

        $container->addServiceProvider(new SessionServiceProvider(
            sessionOptions: $this->getSessionOptions($config),
            handler: $this->getSessionHandler($config, $pathManager),
        ));

        $container->addServiceProvider(new CsrfServiceProvider(
            sessionFactory: fn(): SessionInterface => ContainerDependency::get($container, SessionInterface::class),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function getSessionOptions(ConfigInterface $config): array
    {
        return [
            'cookie_lifetime' => $config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME, 0),
            'cookie_path' => $config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
            'cookie_secure' => $config->getBool(ConfigKey::SESSION_COOKIE_SECURE, false),
            'cookie_httponly' => $config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
            'cookie_domain' => $config->getString(ConfigKey::SESSION_COOKIE_DOMAIN, ''),
            'cookie_samesite' => $config->getString(ConfigKey::SESSION_COOKIE_SAMESITE, 'Lax'),
            'use_only_cookies' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_ONLY_COOKIES, true),
            'use_strict_mode' => $config->getBool(ConfigKey::SESSION_OPTIONS_USE_STRICT_MODE, true),
        ];
    }

    private function getSessionHandler(ConfigInterface $config, PathManager $pathManager): SessionHandlerInterface
    {
        $sessionFilePath = $config->get(ConfigKey::SESSION_FILE_PATH);
        if (!is_string($sessionFilePath) && !is_null($sessionFilePath)) {
            throw new InvalidArgumentException(sprintf(self::INCORRECT_SESSION_FILE_PATH, get_debug_type($sessionFilePath)));
        }

        if ($sessionFilePath === null || $sessionFilePath === '') {
            return new NativeFileSessionHandler();
        }

        return new NativeFileSessionHandler(
            $pathManager->get(PathName::STORAGE, $sessionFilePath),
        );
    }
}
