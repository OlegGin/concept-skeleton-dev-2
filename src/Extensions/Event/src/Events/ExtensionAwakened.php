<?php declare(strict_types=1);

namespace Concept\Extensions\Event\Events;

final readonly class ExtensionAwakened
{
    public function __construct(
        public string $extensionName,
        public string $anchorId,
    ) {}
}
