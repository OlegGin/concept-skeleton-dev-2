<?php declare(strict_types=1);

namespace Concept\Extensions\ViewTwig;

use Concept\Extensions\View\Contracts\ViewInterface;
use Twig\Environment as Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class TwigView implements ViewInterface
{
    public function __construct(
        private readonly Twig $twig,
        private readonly string $defaultExtension,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $viewName, array $data = []): string
    {
        return $this->twig->render($this->ensureExtension($viewName), $data);
    }

    private function ensureExtension(string $viewName): string
    {
        if (str_ends_with($viewName, $this->defaultExtension)) {
            return $viewName;
        }

        return $viewName . $this->defaultExtension;
    }
}
