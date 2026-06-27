<?php declare(strict_types=1);

namespace Concept\testApp\jsonDbApp\src\Foundation;

final class ConfigKey
{
    public const string APP_DEBUG = 'app.debug';
    public const string APP_NAME = 'app.name';
    public const string APP_VERSION = 'app.version';
    public const string APP_TIMEZONE = 'app.timezone';

    public const string LOG_NAME = 'log.name';
    public const string LOG_LEVEL = 'log.level';
    public const string LOG_MAX_FILES = 'log.max_files';

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

    public const string MIGRATIONS_TABLE = 'migrations.table';
    public const string MIGRATIONS_PATHS = 'migrations.paths';

    public const string MASKING_PATTERNS = 'masking.patterns';
    public const string MASKING_KEY_PATTERNS = 'masking.key_patterns';
    public const string MASKING_RULES = 'masking.rules';
}
