<?php declare(strict_types=1);

namespace Concept\Extensions\CastingValinor\Exceptions;

use Exception;
use Throwable;

final class CastingException extends Exception
{
    private const string ERR_MESSAGE_FORMAT = 'Failed to cast provided data to type: %s';

    public function __construct(string $targetType, ?Throwable $previous = null)
    {
        $message = sprintf(self::ERR_MESSAGE_FORMAT, $targetType);
        if ($previous !== null) {
            $message = sprintf('%s. Reason: %s', $message, $previous->getMessage());
        }

        parent::__construct($message, 0, $previous);
    }
}
