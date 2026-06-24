<?php declare(strict_types=1);

namespace Concept\Extensions\Session\Contracts;

use Concept\Extensions\Session\Contracts\FlashBagInterface as ConceptFlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

interface SessionInterface extends FlashBagAwareSessionInterface
{
    public function getFlashBag(): ConceptFlashBagInterface;
}
