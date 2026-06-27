<?php declare(strict_types=1);

namespace Concept\Extensions\SessionSymfony;

use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

final class SessionServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'session-symfony';

    /**
     * @param array<string, mixed> $sessionOptions
     */
    public function __construct(
        private readonly array $sessionOptions,
        private readonly SessionHandlerInterface $handler,
    ) {}

    public function provides(string $id): bool
    {
        return in_array($id, [
            SessionInterface::class,
            FlashBagInterface::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(SessionInterface::class, function() use ($container): Session {
            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: SessionInterface::class,
            ));

            $storage = new NativeSessionStorage($this->sessionOptions, $this->handler);
            $session = new Session($storage, flashes: new FlashBag());

            if (!$session->isStarted()) {
                $session->start();
            }

            return $session;
        })->setShared(true);

        $container->add(FlashBagInterface::class, function() use ($container): FlashBagInterface {
            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);

            return $session->getFlashBag();
        })->setShared(true);
    }
}
