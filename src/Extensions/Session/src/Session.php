<?php declare(strict_types=1);

namespace Concept\Extensions\Session;

use Concept\Extensions\Session\Contracts\FlashBagInterface;
use Concept\Extensions\Session\Contracts\SessionInterface;
use LogicException;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

final class Session extends SymfonySession implements SessionInterface
{
    public function getFlashBag(): FlashBagInterface
    {
        $flashBag = parent::getFlashBag();

        if (!$flashBag instanceof FlashBagInterface) {
            throw new LogicException(sprintf(
                'Session flash bag must be an instance of %s, %s given.',
                FlashBagInterface::class,
                $flashBag::class,
            ));
        }

        return $flashBag;
    }
}
