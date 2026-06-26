<?php declare(strict_types=1);

namespace JsonApp\Foundation;

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

    public const string MASKING_PATTERNS = 'masking.patterns';
    public const string MASKING_KEY_PATTERNS = 'masking.key_patterns';
    public const string MASKING_RULES = 'masking.rules';

    public const string EVENTS_ENABLED = 'events.enabled';
    public const string EVENTS_SUBSCRIBERS = 'events.subscribers';

    public const string TELEMETRY_ENABLED = 'telemetry.enabled';
    public const string TELEMETRY_LOGS = 'telemetry.logs';
}
