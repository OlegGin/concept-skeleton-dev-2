<?php declare(strict_types=1);

namespace Concept\Extensions\ViewPlates;

use Concept\Extensions\View\Contracts\ViewInterface;
use League\Plates\Engine;

final class PlatesView implements ViewInterface
{
    public function __construct(private readonly Engine $engine) {}

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $viewName, array $data = []): string
    {
        return $this->engine->render($viewName, $data);
    }
}
