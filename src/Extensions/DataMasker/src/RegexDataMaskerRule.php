<?php declare(strict_types=1);

namespace Concept\Extensions\DataMasker;

use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;

final class RegexDataMaskerRule implements DataMaskerRuleInterface
{
    /**
     * @param array<string, string> $patterns
     * @param list<string> $keyPatterns
     */
    public function __construct(
        private readonly array $patterns = [],
        private readonly array $keyPatterns = [],
    ) {}

    public function isSensitiveKey(string $key): bool
    {
        return (bool) preg_filter($this->keyPatterns, $key, $key);
    }

    public function apply(string $value): string
    {
        $masked = preg_replace(array_keys($this->patterns), array_values($this->patterns), $value);

        return $masked ?? $value;
    }
}
