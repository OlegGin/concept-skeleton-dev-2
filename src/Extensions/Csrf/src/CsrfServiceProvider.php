<?php declare(strict_types=1);

namespace Concept\Extensions\Csrf;

use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class CsrfServiceProvider extends AbstractServiceProvider
{
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
            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);

            return new CsrfTokenManager($session);
        })->setShared(true);
    }
}
