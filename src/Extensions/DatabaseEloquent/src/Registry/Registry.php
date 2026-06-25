<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Registry;

use Concept\Extensions\DatabaseEloquent\Registry\Contracts\RegistryInterface;

class Registry implements RegistryInterface
{
    /**
     * @var array<string>
     */
    protected array $items = [];

    public function add(string $key, string $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * @param array<string> $values
     */
    public function append(array $values): void
    {
        $this->items = array_merge($this->items, $values);
    }

    /**
     * @return array<string>
     */
    public function all(): array
    {
        return $this->items;
    }
}
