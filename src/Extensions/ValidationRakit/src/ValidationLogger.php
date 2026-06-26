<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit;

use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Monolog\Logger as Monolog;
use Monolog\LogRecord;

final class ValidationLogger
{
    private const string LOG_INCOMING_DATA = 'Validation incoming data [%s]';
    private const string LOG_VALIDATED_DATA = 'Validated data for [%s]';

    public function __construct(
        private readonly bool $enabled,
        private readonly Monolog $monolog,
        ?DataMaskerInterface $masker = null,
    ) {
        if (!$masker) {
            return;
        }

        $this->monolog->pushProcessor(function(LogRecord $record) use ($masker): LogRecord {
            return $record->with(
                context: $masker->mask($record->context),
            );
        });
    }

    /**
     * @param array<mixed> $data
     */
    public function logIncoming(string $requestClass, string $uri, array $data): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->monolog->debug(sprintf(self::LOG_INCOMING_DATA, $requestClass), [
            'uri' => $uri,
            'data' => $data,
        ]);
    }

    /**
     * @param array<mixed> $validData
     * @param array<mixed> $errors
     */
    public function logResult(string $requestClass, bool $isValid, array $validData, array $errors): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->monolog->debug(sprintf(self::LOG_VALIDATED_DATA, $requestClass), [
            'is_valid' => $isValid,
            'valid_data' => $validData,
            'errors' => $errors,
        ]);
    }
}
