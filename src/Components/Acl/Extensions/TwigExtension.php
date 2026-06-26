<?php declare(strict_types=1);

namespace Concept\Components\Acl\Extensions;

use Concept\Components\Acl\Contracts\AclInterface;
use Psr\Container\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    private ?AclInterface $acl = null;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('acl_allowed', $this->isAllowed(...)),
        ];
    }

    public function isAllowed(?string $resource = null, ?string $privilege = null): bool
    {
        return $this->acl()->isAllowed($resource, $privilege);
    }

    private function acl(): AclInterface
    {
        if ($this->acl === null) {
            /** @var AclInterface $acl */
            $acl = $this->container->get(AclInterface::class);
            $this->acl = $acl;
        }

        return $this->acl;
    }
}
