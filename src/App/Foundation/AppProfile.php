<?php declare(strict_types=1);

namespace Concept\App\Foundation;

final class AppProfile
{
    public const string MINIMAL = 'minimal';
    public const string API = 'api';
    public const string FULL = 'full';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MINIMAL,
            self::API,
            self::FULL,
        ];
    }

    public static function isValid(string $profile): bool
    {
        return in_array($profile, self::all(), true);
    }
}
