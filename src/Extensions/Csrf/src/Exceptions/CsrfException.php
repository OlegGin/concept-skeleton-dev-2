<?php declare(strict_types=1);

namespace Concept\Extensions\Csrf\Exceptions;

use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Exception;

final class CsrfException extends Exception
{
    private const string DEFAULT_MESSAGE = 'CSRF token mismatch';

    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::DEFAULT_MESSAGE, HttpStatusCode::PAGE_EXPIRED);
    }
}
