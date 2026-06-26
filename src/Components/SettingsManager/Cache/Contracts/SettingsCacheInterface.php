<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Cache\Contracts;

interface SettingsCacheInterface
{
    public function has(string $key): bool;

    public function get(string $key): mixed;

    public function put(string $key, mixed $value): void;

    public function forget(string $key): void;
}
