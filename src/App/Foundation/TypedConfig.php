<?php declare(strict_types=1);

namespace Concept\App\Foundation;

use Concept\Extensions\Config\Contracts\ConfigInterface;

/**
 * Typed readers for bootstrap glue (PHPStan-friendly wrappers over ConfigInterface::getArray).
 */
final class TypedConfig
{
    public function __construct(
        private readonly ConfigInterface $config,
    ) {}

    /**
     * @return list<string>
     */
    public function stringList(string $key): array
    {
        $items = [];
        foreach ($this->config->getArray($key) as $item) {
            if (is_string($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return list<class-string<T>>
     */
    public function classList(string $key, string $class): array
    {
        $items = [];
        foreach ($this->config->getArray($key) as $item) {
            if (is_string($item) && $item !== '' && is_a($item, $class, true)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return list<class-string>
     */
    public function classStringList(string $key): array
    {
        $items = [];
        foreach ($this->config->getArray($key) as $item) {
            if (is_string($item) && $item !== '' && class_exists($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    public function stringMap(string $key): array
    {
        $map = [];
        foreach ($this->config->getArray($key) as $mapKey => $value) {
            if (is_string($mapKey) && is_string($value)) {
                $map[$mapKey] = $value;
            }
        }

        return $map;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return array<string, class-string<T>>
     */
    public function classMap(string $key, string $class): array
    {
        $map = [];
        foreach ($this->config->getArray($key) as $mapKey => $value) {
            if (is_string($mapKey) && is_string($value) && $value !== '' && is_a($value, $class, true)) {
                $map[$mapKey] = $value;
            }
        }

        return $map;
    }
}
