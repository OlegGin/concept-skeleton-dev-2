<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Services\Contracts;

interface SettingsManagerInterface
{
    public function get(string $key, mixed $default = null, string $group = 'general'): mixed;

    public function set(
        string $key,
        mixed $value,
        string $group = 'general',
        ?string $dataType = null,
        ?int $id = null,
    ): void;

    public function delete(string $key, string $group = 'general'): void;

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array;

    public function has(string $key, string $group = 'general'): bool;
}
