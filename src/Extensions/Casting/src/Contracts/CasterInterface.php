<?php declare(strict_types=1);

namespace Concept\Extensions\Casting\Contracts;

use Concept\Extensions\Casting\Exceptions\CastingException;

interface CasterInterface
{
    /**
     * @throws CastingException
     */
    public function cast(mixed $value, string $type): mixed;
}
