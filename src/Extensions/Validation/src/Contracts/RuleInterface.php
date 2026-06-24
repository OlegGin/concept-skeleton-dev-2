<?php declare(strict_types=1);

namespace Concept\Extensions\Validation\Contracts;

interface RuleInterface
{
    public function passes(mixed $value): bool;

    public function getMessage(): string;

    /**
     * @return list<string>
     */
    public function getRequired(): array;

    /**
     * @return list<string>
     */
    public function getFillable(): array;

    /**
     * @param array<mixed> $params
     */
    public function setParameters(array $params): void;
}
