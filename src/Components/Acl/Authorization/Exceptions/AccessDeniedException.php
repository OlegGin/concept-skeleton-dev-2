<?php declare(strict_types=1);

namespace Concept\Components\Acl\Authorization\Exceptions;

use RuntimeException;

final class AccessDeniedException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $redirectRouteName,
    ) {
        parent::__construct($message);
    }

    public function redirectRouteName(): string
    {
        return $this->redirectRouteName;
    }
}
