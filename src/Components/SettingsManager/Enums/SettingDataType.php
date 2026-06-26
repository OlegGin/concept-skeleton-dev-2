<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Enums;

enum SettingDataType: string
{
    case STRING = 'string';
    case TEXT = 'text';
    case INT = 'int';
    case BOOL = 'bool';
    case FLOAT = 'float';
    case JSON = 'json';

    public static function fromValue(mixed $value): self
    {
        return match (true) {
            is_bool($value) => self::BOOL,
            is_int($value) => self::INT,
            is_float($value) => self::FLOAT,
            is_array($value), is_object($value) => self::JSON,
            default => self::STRING,
        };
    }

    public static function tryFromString(string $dataType): ?self
    {
        return self::tryFrom($dataType);
    }
}
