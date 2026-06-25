<?php declare(strict_types=1);

namespace Concept\App\Session;

use Concept\App\Foundation\ConfigKey;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use InvalidArgumentException;
use Redis;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

final class SessionHandlerFactory
{
    public function __construct(
        private readonly string $defaultFilePath,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createSessionOptions(ConfigInterface $config): array
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

    public function create(ConfigInterface $config): SessionHandlerInterface
    {
        return match ($config->getString(ConfigKey::SESSION_DRIVER, SessionDriver::FILE)) {
            SessionDriver::FILE => $this->createFileHandler($config),
            SessionDriver::REDIS => $this->createRedisHandler($config),
            SessionDriver::PDO => $this->createPdoHandler($config),
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported session driver "%s".',
                $config->getString(ConfigKey::SESSION_DRIVER),
            )),
        };
    }

    private function createFileHandler(ConfigInterface $config): NativeFileSessionHandler
    {
        $path = $config->get(ConfigKey::SESSION_FILE_PATH);

        if ($path === null) {
            return new NativeFileSessionHandler($this->defaultFilePath);
        }

        if (!is_string($path)) {
            throw new InvalidArgumentException(sprintf(
                'Session file path must be a string or null, %s given.',
                get_debug_type($path),
            ));
        }

        return new NativeFileSessionHandler($path !== '' ? $path : null);
    }

    private function createRedisHandler(ConfigInterface $config): RedisSessionHandler
    {
        if (!extension_loaded('redis')) {
            throw new InvalidArgumentException(
                'Redis session driver requires ext-redis.',
            );
        }

        $url = $config->getString(ConfigKey::SESSION_REDIS_URL);
        $prefix = $config->getString(ConfigKey::SESSION_REDIS_PREFIX, 'sess_');
        $redis = new Redis();
        $this->connectRedis($redis, $url);

        return new RedisSessionHandler($redis, ['prefix' => $prefix]);
    }

    private function createPdoHandler(ConfigInterface $config): PdoSessionHandler
    {
        $dsn = $config->getString(ConfigKey::SESSION_PDO_DSN);

        if ($dsn === '') {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config->getString(ConfigKey::DB_DRIVER, 'mysql'),
                $config->getString(ConfigKey::DB_HOST, '127.0.0.1'),
                $config->getInt(ConfigKey::DB_PORT, 3306),
                $config->getString(ConfigKey::DB_DATABASE),
                $config->getString(ConfigKey::DB_CHARSET, 'utf8mb4'),
            );
        }

        return new PdoSessionHandler($dsn, [
            'db_table' => $config->getString(ConfigKey::SESSION_PDO_TABLE, 'sessions'),
            'db_username' => $config->getString(ConfigKey::DB_USERNAME),
            'db_password' => $config->getString(ConfigKey::DB_PASSWORD),
        ]);
    }

    private function connectRedis(Redis $redis, string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['host'])) {
            throw new InvalidArgumentException(sprintf('Invalid Redis URL "%s".', $url));
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 6379;
        $timeout = 2.0;

        if (!$redis->connect($host, (int) $port, $timeout)) {
            throw new InvalidArgumentException(sprintf('Unable to connect to Redis at "%s".', $url));
        }

        if (isset($parts['pass'])) {
            $redis->auth($parts['pass']);
        }

        if (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') {
            $redis->select((int) ltrim($parts['path'], '/'));
        }
    }
}
