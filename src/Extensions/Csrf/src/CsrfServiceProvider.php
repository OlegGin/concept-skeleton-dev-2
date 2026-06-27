<?php declare(strict_types=1);

namespace Concept\Extensions\Csrf;

use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class CsrfServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'csrf';

    public function provides(string $id): bool
    {
        return in_array($id, [
            CsrfTokenManagerInterface::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(CsrfTokenManagerInterface::class, function() use ($container): CsrfTokenManager {
            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: CsrfTokenManagerInterface::class,
            ));

            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);

            return new CsrfTokenManager($session);
        })->setShared(true);
    }
}
