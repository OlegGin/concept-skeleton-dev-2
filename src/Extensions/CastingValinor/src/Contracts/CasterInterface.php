<?php declare(strict_types=1);

namespace Concept\Extensions\CastingValinor\Contracts;

use Concept\Extensions\CastingValinor\Exceptions\CastingException;

interface CasterInterface
{
    /**
     * @throws CastingException
     */
    public function cast(mixed $value, string $type): mixed;
}
