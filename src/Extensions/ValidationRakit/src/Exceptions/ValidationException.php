<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit\Exceptions;

use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Exception;

final class ValidationException extends Exception
{
    private const string DEFAULT_MESSAGE = 'Validation failed';

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed> $oldData
     */
    public function __construct(
        private readonly array $errors,
        private readonly array $oldData,
    ) {
        parent::__construct(self::DEFAULT_MESSAGE, HttpStatusCode::UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOldData(): array
    {
        return $this->oldData;
    }
}
