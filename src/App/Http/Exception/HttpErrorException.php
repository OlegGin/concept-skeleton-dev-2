<?php declare(strict_types=1);

namespace Concept\App\Http\Exception;

use RuntimeException;

final class HttpErrorException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
