<?php declare(strict_types=1);

namespace Concept\App\Foundation;

use Concept\Extensions\Config\Contracts\ConfigInterface;

/**
 * App-specific composite config shapes for Stack bootstrap (glue).
 * Scalars — via ConfigInterface / ConfigKey in bootstrap; not one-line wrappers here.
 */
final class AppConfig
{
    public function __construct(
        private readonly ConfigInterface $config,
    ) {}

    /**
     * Database connection array for DatabaseBuilder::setConnection().
     *
     * @return array{
     *     driver: string,
     *     host: string,
     *     port: int,
     *     database: string,
     *     username: string,
     *     password: string,
     *     charset: string,
     *     collation: string,
     *     prefix: string
     * }
     */
    public function dbConnection(): array
    {
        return [
            'driver' => $this->config->getString(ConfigKey::DB_DRIVER),
            'host' => $this->config->getString(ConfigKey::DB_HOST),
            'port' => $this->config->getInt(ConfigKey::DB_PORT, 3306),
            'database' => $this->config->getString(ConfigKey::DB_DATABASE),
            'username' => $this->config->getString(ConfigKey::DB_USERNAME),
            'password' => $this->config->getString(ConfigKey::DB_PASSWORD),
            'charset' => $this->config->getString(ConfigKey::DB_CHARSET, 'utf8mb4'),
            'collation' => $this->config->getString(ConfigKey::DB_COLLATION, 'utf8mb4_unicode_ci'),
            'prefix' => $this->config->getString(ConfigKey::DB_PREFIX),
        ];
    }

    /**
     * Session cookie options (subset used by recipe bootstraps).
     *
     * @return array{
     *     cookie_lifetime: int,
     *     cookie_path: string,
     *     cookie_secure: bool,
     *     cookie_httponly: bool,
     *     cookie_samesite: string
     * }
     */
    public function sessionCookieOptions(): array
    {
        return [
            'cookie_lifetime' => $this->config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME),
            'cookie_path' => $this->config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
            'cookie_secure' => $this->config->getBool(ConfigKey::SESSION_COOKIE_SECURE),
            'cookie_httponly' => $this->config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
            'cookie_samesite' => $this->config->getString(ConfigKey::SESSION_COOKIE_SAMESITE, 'Lax'),
        ];
    }

    /**
     * Full session options for ApplicationStackBootstrap.
     *
     * @return array{
     *     cookie_lifetime: int,
     *     cookie_path: string,
     *     cookie_secure: bool,
     *     cookie_httponly: bool,
     *     cookie_domain: string,
     *     cookie_samesite: string,
     *     use_only_cookies: bool,
     *     use_strict_mode: bool
     * }
     */
    public function sessionOptions(): array
    {
        return [
            ...$this->sessionCookieOptions(),
            'cookie_domain' => $this->config->getString(ConfigKey::SESSION_COOKIE_DOMAIN),
            'use_only_cookies' => $this->config->getBool(ConfigKey::SESSION_OPTIONS_USE_ONLY_COOKIES, true),
            'use_strict_mode' => $this->config->getBool(ConfigKey::SESSION_OPTIONS_USE_STRICT_MODE, true),
        ];
    }
}
