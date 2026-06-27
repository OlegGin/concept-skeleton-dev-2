<?php declare(strict_types=1);

namespace Concept\Extensions\CastingValinor;

use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class CastingServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'casting-valinor';

    /**
     * @param list<class-string> $transformerClasses
     */
    public function __construct(
        private readonly string $cacheDirectory,
        private readonly array $transformerClasses = [],
        private readonly bool $debug = false,
    ) {}

    public function provides(string $id): bool
    {
        return $id === CasterInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(CasterInterface::class, function() use ($container): CasterInterface {
            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: CasterInterface::class,
            ));

            return new Caster(
                $this->cacheDirectory,
                $this->transformerClasses,
                $this->debug,
            );
        })->setShared(true);
    }
}
