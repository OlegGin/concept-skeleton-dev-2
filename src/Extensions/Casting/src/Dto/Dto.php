<?php declare(strict_types=1);

namespace Concept\Extensions\Casting\Dto;

use Concept\Extensions\Casting\Contracts\DtoInterface;

class Dto implements DtoInterface
{
    public function toArray(): array
    {
        return (array) $this;
    }
}
