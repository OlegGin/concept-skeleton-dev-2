<?php declare(strict_types=1);

namespace Concept\Extensions\DataMasker\Contracts;

interface DataMaskerInterface
{
    public function mask(mixed $data): mixed;

    public function clearRules(): void;
}
