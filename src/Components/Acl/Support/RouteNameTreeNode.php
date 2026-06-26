<?php declare(strict_types=1);

namespace Concept\Components\Acl\Support;

final class RouteNameTreeNode
{
    /** @var array<string, self> */
    private array $children = [];

    /** @var list<array{name: string, path: string}> */
    private array $routes = [];

    public function child(string $segment): self
    {
        if (!isset($this->children[$segment])) {
            $this->children[$segment] = new self();
        }

        return $this->children[$segment];
    }

    /**
     * @param array{name: string, path: string} $route
     */
    public function addRoute(array $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * @return array<string, self>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * @return list<array{name: string, path: string}>
     */
    public function routes(): array
    {
        return $this->routes;
    }
}
