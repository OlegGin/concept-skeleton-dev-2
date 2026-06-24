<?php declare(strict_types=1);

namespace Concept\App\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(private readonly string $appName) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_name', fn (): string => $this->appName),
        ];
    }
}
