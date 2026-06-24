<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Routing;

use Concept\Extensions\Http\Contracts\UrlGeneratorInterface;
use League\Route\Router;
use Psr\Http\Message\ServerRequestInterface;

final class UrlGenerator implements UrlGeneratorInterface
{
    public function __construct(private readonly Router $router) {}

    public function base(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $baseUrl = $uri->getScheme() . '://' . $uri->getHost();

        $port = $uri->getPort();
        if ($port !== null && !in_array($port, [80, 443], true)) {
            $baseUrl .= ':' . $port;
        }

        return $baseUrl;
    }

    public function uri(string $name, array $parameters = []): string
    {
        return $this->router->getNamedRoute($name)->getPath($parameters);
    }

    public function url(ServerRequestInterface $request, string $name, array $parameters = []): string
    {
        return $this->build($this->base($request), $this->uri($name, $parameters));
    }

    private function build(string $baseUrl, string $uri): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
    }
}
