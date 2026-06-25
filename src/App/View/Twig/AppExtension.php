<?php declare(strict_types=1);

namespace Concept\App\View\Twig;

use Concept\App\Foundation\ConfigKey;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(private readonly ConfigInterface $config) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'app_name',
                fn(): string => $this->config->getString(ConfigKey::APP_NAME),
            ),
        ];
    }
}
