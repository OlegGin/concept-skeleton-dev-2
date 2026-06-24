<?php declare(strict_types=1);

namespace Concept\Extensions\SessionSymfony;

use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
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
