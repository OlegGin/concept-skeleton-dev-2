<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Extensions;

use Concept\Components\AuthAdmin\Services\AuthService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    public function __construct(private readonly AuthService $auth) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('auth', fn() => $this->auth),
        ];
    }
}