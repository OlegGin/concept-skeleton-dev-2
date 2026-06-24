<?php declare(strict_types=1);

namespace Concept\Extensions\DataMasker;

use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use ReflectionObject;

final class DataMasker implements DataMaskerInterface
{
    public const string MASK_CHARS = '***';

    /** @var list<DataMaskerRuleInterface> */
    private array $rules = [];

    public function addRule(DataMaskerRuleInterface $rule): void
    {
        $this->rules[] = $rule;
    }

    public function clearRules(): void
    {
        $this->rules = [];
    }

    public function mask(mixed $data): mixed
    {
        if ($this->rules === []) {
            return $data;
        }

        if (is_array($data)) {
            /** @var array<mixed> $cloned */
            $cloned = $this->deepClone($data);

            return $this->maskArray($cloned);
        }

        if (is_object($data)) {
            /** @var object $cloned */
            $cloned = $this->deepClone($data);

            return $this->maskObject($cloned);
        }

        if (is_string($data)) {
            return $this->maskString($data);
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function maskArray(array $data): array
    {
        foreach ($data as $key => &$value) {
            if ($this->isSensitiveKey((string) $key)) {
                $value = self::MASK_CHARS;
                continue;
            }

            $value = $this->maskRecursive($value);
        }

        return $data;
    }

    private function maskObject(object $data): object
    {
        $reflection = new ReflectionObject($data);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            if ($this->isSensitiveKey($property->getName())) {
                $property->setValue($data, self::MASK_CHARS);
                continue;
            }

            $property->setValue($data, $this->maskRecursive($property->getValue($data)));
        }

        return $data;
    }

    private function maskString(string $data): string
    {
        foreach ($this->rules as $rule) {
            $data = $rule->apply($data);
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->isSensitiveKey($key)) {
                return true;
            }
        }

        return false;
    }

    private function maskRecursive(mixed $data): mixed
    {
        if ($this->rules === []) {
            return $data;
        }

        if (is_array($data)) {
            return $this->maskArray($data);
        }

        if (is_object($data)) {
            return $this->maskObject($data);
        }

        if (is_string($data)) {
            return $this->maskString($data);
        }

        return $data;
    }

    private function deepClone(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->deepClone(...), $value);
        }

        if (is_object($value)) {
            return clone $value;
        }

        return $value;
    }
}
