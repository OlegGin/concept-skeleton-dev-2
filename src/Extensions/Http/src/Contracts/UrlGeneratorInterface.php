<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Contracts;

use Psr\Http\Message\ServerRequestInterface;

interface UrlGeneratorInterface
{
    public function base(ServerRequestInterface $request): string;

    /**
     * @param array<string, mixed> $parameters
     */
    public function uri(string $name, array $parameters = []): string;

    /**
     * @param array<string, mixed> $parameters
     */
    public function url(ServerRequestInterface $request, string $name, array $parameters = []): string;
}
