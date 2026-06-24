<?php declare(strict_types=1);

namespace Concept\Extensions\Csrf\Contracts;

interface CsrfTokenManagerInterface
{
    public function getToken(): string;

    public function validate(?string $token): bool;
}
