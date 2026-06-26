<?php declare(strict_types=1);

namespace Concept\Components\Acl\Support;

use Concept\Core\Http\Routing\RouteDescriptor;

final class NamedRouteCatalog
{
    public function __construct(
        private readonly RouteDescriptor $routeDescriptor,
    ) {}

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_column($this->entries(), 'name');
    }

    /**
     * @return list<array{name: string, path: string, depth: int}>
     */
    public function tree(): array
    {
        $builder = new RouteNameTreeBuilder();

        foreach ($this->entries() as $entry) {
            $builder->add($entry);
        }

        return $builder->flatten();
    }

    /**
     * @return list<array{name: string, path: string, depth: int}>
     */
    public function treeWithSelected(?string $selected): array
    {
        $tree = $this->tree();
        if ($selected === null || $selected === '' || $this->containsName($tree, $selected)) {
            return $tree;
        }

        array_unshift($tree, ['name' => $selected, 'path' => '', 'depth' => 0]);

        return $tree;
    }

    /**
     * @return list<array{name: string, path: string}>
     */
    private function entries(): array
    {
        /** @var array<string, array{name: string, path: string}> $indexed */
        $indexed = [];

        foreach ($this->routeDescriptor->all() as $route) {
            $name = $route->getName();
            if (!is_string($name) || $name === '' || isset($indexed[$name])) {
                continue;
            }

            $indexed[$name] = [
                'name' => $name,
                'path' => $route->getPath(),
            ];
        }

        $entries = array_values($indexed);
        usort(
            $entries,
            static fn (array $left, array $right): int => $left['name'] <=> $right['name'],
        );

        return $entries;
    }

    /**
     * @param list<array{name: string, path: string, depth: int}> $tree
     */
    private function containsName(array $tree, string $name): bool
    {
        foreach ($tree as $item) {
            if ($item['name'] === $name) {
                return true;
            }
        }

        return false;
    }
}
