<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit\Exceptions;

use LogicException;

final class ValidationLogicException extends LogicException
{
    private const string ERR_VALIDATION_NOT_READY =
        'Validation has not been performed on class %s. You must call validate() before accessing validated data.';

    public function __construct(string $className)
    {
        parent::__construct(sprintf(self::ERR_VALIDATION_NOT_READY, $className));
    }
}
