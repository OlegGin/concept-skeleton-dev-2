<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Registry\Contracts;

interface RegistryInterface
{
    public function add(string $key, string $value): void;

    /**
     * @param array<string> $values
     */
    public function append(array $values): void;

    /**
     * @return array<string>
     */
    public function all(): array;
}
