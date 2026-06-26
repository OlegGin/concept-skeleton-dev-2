<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Support;

use Concept\Components\SettingsManager\Enums\SettingDataType;
use InvalidArgumentException;
use JsonException;

final class SettingValueCodec
{
    public function determineDataType(mixed $value): string
    {
        return SettingDataType::fromValue($value)->value;
    }

    public function serialize(mixed $value, string $dataType): string
    {
        $type = SettingDataType::tryFromString($dataType)
            ?? throw new InvalidArgumentException(sprintf('Unsupported data type: %s', $dataType));

        return match ($type) {
            SettingDataType::BOOL => $this->serializeBool($value),
            SettingDataType::INT => $this->serializeInt($value),
            SettingDataType::FLOAT => $this->serializeFloat($value),
            SettingDataType::JSON => $this->serializeJson($value),
            SettingDataType::STRING, SettingDataType::TEXT => $this->serializeString($value),
        };
    }

    public function cast(string $value, string $dataType): mixed
    {
        $type = SettingDataType::tryFromString($dataType)
            ?? throw new InvalidArgumentException(sprintf('Unsupported data type: %s', $dataType));

        return match ($type) {
            SettingDataType::BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingDataType::INT => (int)$value,
            SettingDataType::FLOAT => (float)$value,
            SettingDataType::JSON => $this->decodeJson($value),
            SettingDataType::STRING, SettingDataType::TEXT => $value,
        };
    }

    private function serializeBool(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    private function serializeInt(mixed $value): string
    {
        if (is_int($value)) {
            return (string)$value;
        }

        if (is_string($value) || is_float($value)) {
            return (string)(int)$value;
        }

        throw new InvalidArgumentException('Value cannot be serialized as int.');
    }

    private function serializeFloat(mixed $value): string
    {
        if (is_float($value) || is_int($value)) {
            return (string)(float)$value;
        }

        if (is_string($value)) {
            return (string)(float)$value;
        }

        throw new InvalidArgumentException('Value cannot be serialized as float.');
    }

    private function serializeString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }

        throw new InvalidArgumentException('Value cannot be serialized as string.');
    }

    private function serializeJson(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Value cannot be encoded as JSON.', 0, $exception);
        }
    }

    /**
     * @return array<mixed, mixed>|object
     */
    private function decodeJson(string $value): array|object
    {
        try {
            $decoded = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Stored JSON value is invalid.', 0, $exception);
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        if (is_object($decoded)) {
            return $decoded;
        }

        throw new InvalidArgumentException('Stored JSON value must decode to an array or object.');
    }
}
