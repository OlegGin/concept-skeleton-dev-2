<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit\Rules;

use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;

abstract class Rule implements RuleInterface
{
    protected string $message = 'The :attribute is invalid';

    /** @var list<string> */
    protected array $fillable = [];

    /** @var list<string> */
    protected array $required = [];

    /** @var array<string, mixed> */
    protected array $params = [];

    abstract public function passes(mixed $value): bool;

    protected function parameter(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setParameters(array $params): void
    {
        $this->params = $params;
    }

    public function getRequired(): array
    {
        return $this->required;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
