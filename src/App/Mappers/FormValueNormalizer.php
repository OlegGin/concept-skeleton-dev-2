<?php declare(strict_types=1);

namespace Concept\App\Mappers;

final class FormValueNormalizer
{
    public static function nullableString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $value;
    }

    public static function nullableInt(int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public static function toBool(string|bool|null $value): bool
    {
        if ($value === null || $value === '' || $value === false || $value === 'off') {
            return false;
        }

        if ($value === true) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
