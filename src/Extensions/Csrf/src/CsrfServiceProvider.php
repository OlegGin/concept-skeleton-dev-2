<?php declare(strict_types=1);

namespace Concept\Extensions\Csrf;

use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\Csrf\Middleware\VerifyCsrfTokenMiddleware;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class CsrfServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            CsrfTokenManagerInterface::class,
            VerifyCsrfTokenMiddleware::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(CsrfTokenManagerInterface::class, function () use ($container): CsrfTokenManager {
            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);

            return new CsrfTokenManager($session);
        })->setShared(true);

        $container->add(VerifyCsrfTokenMiddleware::class, function () use ($container): VerifyCsrfTokenMiddleware {
            /** @var CsrfTokenManagerInterface $csrfTokenManager */
            $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

            return new VerifyCsrfTokenMiddleware($csrfTokenManager);
        })->setShared(true);
    }
}
