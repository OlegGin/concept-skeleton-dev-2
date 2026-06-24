<?php declare(strict_types=1);

namespace Concept\Extensions\Csrf;

use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\Csrf\Protocol\CsrfField;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;

final class CsrfTokenManager implements CsrfTokenManagerInterface
{
    private const int TOKEN_LENGTH = 32;

    public function __construct(
        private readonly SessionInterface $session,
    ) {}

    public function getToken(): string
    {
        if (!$this->session->has(CsrfField::NAME)) {
            $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
            $this->session->set(CsrfField::NAME, $token);
        }

        $token = $this->session->get(CsrfField::NAME);
        if (!is_string($token)) {
            return '';
        }

        return $token;
    }

    public function validate(?string $token): bool
    {
        if ($token === null || $token === '' || !$this->session->has(CsrfField::NAME)) {
            return false;
        }

        $sessionToken = $this->session->get(CsrfField::NAME);
        if (!is_string($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
