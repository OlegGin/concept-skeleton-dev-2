<?php declare(strict_types=1);

namespace Concept\Extensions\Validation\Contracts;

interface ValidatorInterface
{
    /**
     * @param array<string, class-string<RuleInterface>> $rules
     */
    public function addRules(array $rules): void;

    /**
     * @param array<mixed> $data
     * @param array<mixed> $rulesConfig
     */
    public function make(array $data, array $rulesConfig): ValidationInterface;
}
