<?php declare(strict_types=1);

namespace Concept\App\Foundation;

final class PathName
{
    public const string CONFIG = 'config';
    public const string PUBLIC = 'public';
    public const string VIEWS = 'resources/views';
    public const string LOGS = 'storage/logs';
    public const string CACHE = 'storage/cache';
    public const string ERRORS_FALLBACK_VIEWS = 'resources/views/errors/fallback';

    /**
     * @return array<string, string>
     */
    public static function map(): array
    {
        $paths = [
            self::CONFIG,
            self::PUBLIC,
            self::VIEWS,
            self::LOGS,
            self::CACHE,
            self::ERRORS_FALLBACK_VIEWS,
        ];

        return array_combine($paths, $paths);
    }
}
