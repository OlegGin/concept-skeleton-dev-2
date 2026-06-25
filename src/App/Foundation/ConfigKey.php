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
    public const string DB_DATABASE = 'db.database';
    public const string DB_USERNAME = 'db.username';
    public const string DB_PASSWORD = 'db.password';
    public const string DB_CHARSET = 'db.charset';
    public const string DB_COLLATION = 'db.collation';
    public const string DB_PREFIX = 'db.prefix';

    public const string LOG_NAME = 'log.name';
    public const string LOG_LEVEL = 'log.level';
    public const string LOG_MAX_FILES = 'log.max_files';
    public const string LOG_DB_QUERIES = 'log.db_queries';
    public const string LOG_VALIDATION_DATA = 'log.validation_data';

    public const string CASTER_TRANSFORMERS = 'caster.transformers';

    public const string MASKING_PATTERNS = 'masking.patterns';
    public const string MASKING_KEY_PATTERNS = 'masking.key_patterns';
    public const string MASKING_RULES = 'masking.rules';

    public const string MIGRATIONS_TABLE = 'migrations.table';
    public const string MIGRATIONS_PATHS = 'migrations.paths';

    public const string SEEDERS_LIST = 'seeders.list';

    public const string PAGINATION_PER_PAGE = 'pagination.per_page';

    public const string VIEW_PATHS = 'view.paths';
    public const string VIEW_EXTENSIONS = 'view.extensions';
    public const string VIEW_CONTEXTS = 'view.contexts';
}
