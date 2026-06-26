<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Cache;

use Concept\Components\SettingsManager\Cache\Contracts\SettingsCacheInterface;

final class InMemorySettingsCache implements SettingsCacheInterface
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function get(string $key): mixed
    {
        return $this->items[$key];
    }

    public function put(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }
}
