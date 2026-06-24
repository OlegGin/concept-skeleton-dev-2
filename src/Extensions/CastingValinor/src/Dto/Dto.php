<?php declare(strict_types=1);

namespace Concept\Extensions\CastingValinor\Dto;

use Concept\Extensions\CastingValinor\Contracts\DtoInterface;

class Dto implements DtoInterface
{
    public function toArray(): array
    {
        return (array) $this;
    }
}
