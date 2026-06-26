<?php declare(strict_types=1);

namespace Concept\App\Foundation;

/**
 * Canonical configuration keys for this application.
 */
final class ConfigKey
{
    public const string APP_DEBUG = 'app.debug';
    public const string APP_NAME = 'app.name';
    public const string APP_VERSION = 'app.version';
    public const string APP_TIMEZONE = 'app.timezone';
    public const string APP_LOCALE = 'app.locale';
    public const string APP_FALLBACK_LOCALE = 'app.fallback_locale';

    public const string COMMANDS = 'commands';

    public const string ROUTES_LIST = 'routes.list';
    public const string ROUTES_INTERCEPTORS = 'routes.interceptors';

    public const string DB_DRIVER = 'db.driver';
    public const string DB_HOST = 'db.host';
    public const string DB_PORT = 'db.port';
    public const string DB_DATABASE = 'db.database';
    public const string DB_USERNAME = 'db.username';
    public const string DB_PASSWORD = 'db.password';
    public const string DB_CHARSET = 'db.charset';
    public const string DB_COLLATION = 'db.collation';
    public const string DB_PREFIX = 'db.prefix';

    public const string DB_LOG_ENABLED = 'db.log_enabled';
    public const string DB_LOG_PATH = 'db.log_path';
    public const string DB_LOG_MAX_FILES = 'db.log_max_files';

    public const string LOG_NAME = 'log.name';
    public const string LOG_LEVEL = 'log.level';
    public const string LOG_MAX_FILES = 'log.max_files';

    public const string VALIDATOR_RULES = 'validator.rules';
    public const string VALIDATOR_LOG_ENABLED = 'validator.log_enabled';
    public const string VALIDATOR_LOG_PATH = 'validator.log_path';
    public const string VALIDATOR_LOG_MAX_FILES = 'validator.log_max_files';

    public const string CASTER_TRANSFORMERS = 'caster.transformers';

    public const string MASKING_PATTERNS = 'masking.patterns';
    public const string MASKING_KEY_PATTERNS = 'masking.key_patterns';
    public const string MASKING_RULES = 'masking.rules';

    public const string MIGRATIONS_TABLE = 'migrations.table';
    public const string MIGRATIONS_PATHS = 'migrations.paths';

    public const string SEEDERS_LIST = 'seeders.list';

    public const string SESSION_DRIVER = 'session.driver';

    public const string SESSION_COOKIE_LIFETIME = 'session.cookie.lifetime';
    public const string SESSION_COOKIE_PATH = 'session.cookie.path';
    public const string SESSION_COOKIE_SECURE = 'session.cookie.secure';
    public const string SESSION_COOKIE_HTTPONLY = 'session.cookie.httponly';
    public const string SESSION_COOKIE_DOMAIN = 'session.cookie.domain';
    public const string SESSION_COOKIE_SAMESITE = 'session.cookie.samesite';

    public const string SESSION_OPTIONS_USE_ONLY_COOKIES = 'session.options.use_only_cookies';
    public const string SESSION_OPTIONS_USE_STRICT_MODE = 'session.options.use_strict_mode';

    public const string SESSION_FILE_PATH = 'session.file.path';

    public const string SESSION_REDIS_URL = 'session.redis.url';
    public const string SESSION_REDIS_PREFIX = 'session.redis.prefix';

    public const string SESSION_PDO_DSN = 'session.pdo.dsn';
    public const string SESSION_PDO_TABLE = 'session.pdo.table';

    public const string PAGINATION_PER_PAGE = 'pagination.per_page';
    public const string PAGINATION_PAGE_NAME = 'pagination.page_name';

    public const string VIEW_PATHS = 'view.paths';
    public const string VIEW_EXTENSIONS = 'view.extensions';
    public const string VIEW_CONTEXTS = 'view.contexts';
}
