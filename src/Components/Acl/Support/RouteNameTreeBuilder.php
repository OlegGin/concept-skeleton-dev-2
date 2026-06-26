<?php declare(strict_types=1);

namespace Concept\Components\Acl\Support;

final class RouteNameTreeBuilder
{
    private RouteNameTreeNode $root;

    public function __construct()
    {
        $this->root = new RouteNameTreeNode();
    }

    /**
     * @param array{name: string, path: string} $entry
     */
    public function add(array $entry): void
    {
        $parts = explode('.', $entry['name']);
        $node = $this->root;
        $lastIndex = count($parts) - 1;

        for ($i = 0; $i < $lastIndex; $i++) {
            $node = $node->child($parts[$i]);
        }

        $node->addRoute($entry);
    }

    /**
     * @return list<array{name: string, path: string, depth: int}>
     */
    public function flatten(): array
    {
        /** @var list<array{name: string, path: string, depth: int}> $result */
        $result = [];
        $this->walk($this->root, 1, $result);

        return $result;
    }

    /**
     * @param list<array{name: string, path: string, depth: int}> $result
     */
    private function walk(RouteNameTreeNode $node, int $depth, array &$result): void
    {
        $routes = $node->routes();
        usort(
            $routes,
            static fn (array $left, array $right): int => $left['name'] <=> $right['name'],
        );

        foreach ($routes as $route) {
            $result[] = [
                'name' => $route['name'],
                'path' => $route['path'],
                'depth' => $depth,
            ];
        }

        $children = $node->children();
        ksort($children);

        foreach ($children as $child) {
            $this->walk($child, $depth + 1, $result);
        }
    }
}
