<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Mappers;

use Concept\App\Mappers\FormValueNormalizer;
use Concept\Components\SettingsManager\Enums\SettingDataType;
use Concept\Components\SettingsManager\Models\SettingModel;
use Concept\Components\SettingsManager\Support\SettingValueCodec;
use InvalidArgumentException;
use JsonException;

final class SettingFormValueMapper
{
    public function __construct(private readonly SettingValueCodec $codec) {}

    public function fromForm(string $rawValue, string $dataType): mixed
    {
        $type = SettingDataType::tryFromString($dataType)
            ?? throw new InvalidArgumentException(sprintf('Unsupported data type: %s', $dataType));

        return match ($type) {
            SettingDataType::BOOL => FormValueNormalizer::toBool($rawValue),
            SettingDataType::JSON => $this->decodeJsonInput($rawValue),
            SettingDataType::INT => $this->parseInt($rawValue),
            SettingDataType::FLOAT => $this->parseFloat($rawValue),
            SettingDataType::STRING, SettingDataType::TEXT => $rawValue,
        };
    }

    public function toFormValue(SettingModel $setting): string
    {
        $type = SettingDataType::tryFromString($setting->getDataType())
            ?? SettingDataType::STRING;

        $value = $this->codec->cast($setting->getSettingValue(), $type->value);

        return match ($type) {
            SettingDataType::BOOL => $value ? '1' : '0',
            SettingDataType::JSON => $this->encodeJsonForForm($value),
            default => $this->stringifyScalar($value),
        };
    }

    public function toDisplayValue(SettingModel $setting): string
    {
        $type = SettingDataType::tryFromString($setting->getDataType())
            ?? SettingDataType::STRING;

        $value = $this->codec->cast($setting->getSettingValue(), $type->value);

        return match ($type) {
            SettingDataType::BOOL => $value ? 'true' : 'false',
            SettingDataType::JSON => $this->encodeJsonForForm($value),
            default => $this->stringifyScalar($value),
        };
    }

    /**
     * @return array<mixed, mixed>|object
     */
    private function decodeJsonInput(string $rawValue): array|object
    {
        try {
            $decoded = json_decode($rawValue, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Setting value must be valid JSON.', 0, $exception);
        }

        if (!is_array($decoded) && !is_object($decoded)) {
            throw new InvalidArgumentException('Setting value must decode to a JSON object or array.');
        }

        return $decoded;
    }

    private function encodeJsonForForm(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Setting value cannot be encoded as JSON.', 0, $exception);
        }
    }

    private function parseInt(string $rawValue): int
    {
        if (!is_numeric($rawValue)) {
            throw new InvalidArgumentException('Setting value must be a valid integer.');
        }

        return (int)$rawValue;
    }

    private function parseFloat(string $rawValue): float
    {
        if (!is_numeric($rawValue)) {
            throw new InvalidArgumentException('Setting value must be a valid number.');
        }

        return (float)$rawValue;
    }

    private function stringifyScalar(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }

        throw new InvalidArgumentException('Setting value cannot be converted to string.');
    }
}
