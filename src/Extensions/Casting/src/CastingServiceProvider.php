<?php declare(strict_types=1);

namespace Concept\Extensions\Casting;

use Concept\Extensions\Casting\Contracts\CasterInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class CastingServiceProvider extends AbstractServiceProvider
{
    /**
     * @param list<class-string> $transformerClasses
     */
    public function __construct(
        private readonly string $cacheDirectory,
        private readonly bool $debug = false,
        private readonly array $transformerClasses = [],
    ) {}

    public function provides(string $id): bool
    {
        return $id === CasterInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(CasterInterface::class, function () {
            return new Caster(
                $this->cacheDirectory,
                $this->debug,
                $this->transformerClasses,
            );
        })->setShared(true);
    }
}
