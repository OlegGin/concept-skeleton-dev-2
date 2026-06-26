<?php declare(strict_types=1);

namespace Concept\App\View\Twig;

use Concept\App\Foundation\ConfigKey;
use Concept\Extensions\Components\ComponentRegistry;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Http\Contracts\UrlGeneratorInterface;
use Concept\Extensions\View\View\ViewContextResolver;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Main extension class for application-specific Twig logic.
 * Located in Extensions/Twig to allow future expansion of other system parts.
 */
class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ViewContextResolver $routeNamespaceResolver,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ServerRequestInterface $request,
        private readonly ComponentRegistry $componentRegistry,
    ) {}

    /**
     * Register custom filters here.
     * new TwigFilter('example', [$this, 'exampleFilter']),
     * Usage in Twig: {{ var | my_filter }}
     */
    public function getFilters(): array
    {
        return [];
    }

    /**
     * Register custom functions here.
     * new TwigFunction('example', fn() => $this->example),
     * Usage in Twig: {{ my_function() }}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', [$this->config, 'get']),
            new TwigFunction('route_namespace', fn(): ?string => $this->routeNamespaceResolver->resolve($this->request)),
            new TwigFunction('uri', [$this->urlGenerator, 'uri']),
            new TwigFunction('url', [$this->urlGenerator, 'url']),
            new TwigFunction('base_url', [$this->urlGenerator, 'base']),
            new TwigFunction('has_component', fn(string $name): bool => $this->componentRegistry->has($name)),
        ];
    }
}
