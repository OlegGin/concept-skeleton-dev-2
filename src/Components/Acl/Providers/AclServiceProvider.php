<?php declare(strict_types=1);

namespace Concept\Components\Acl\Providers;

use Concept\Components\Acl\Authorization\AclBuilder;
use Concept\Components\Acl\Authorization\AclGate;
use Concept\Components\Acl\Contracts\AclDefinitionSourceInterface;
use Concept\Components\Acl\Contracts\AclInterface;
use Concept\Components\Acl\Contracts\RoleResolverInterface;
use Concept\Components\Acl\DefinitionSources\ConfigAclDefinitionSource;
use Concept\Components\Acl\DefinitionSources\DatabaseAclDefinitionSource;
use Concept\Components\Acl\RoleResolvers\DefaultRoleResolver;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Laminas\Permissions\Acl\Acl;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class AclServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            AclDefinitionSourceInterface::class,
            RoleResolverInterface::class,
            Acl::class,
            AclInterface::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(AclDefinitionSourceInterface::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            $storage = $config->getString('acl.storage', 'database');

            if ($storage === 'database') {
                return $container->get(DatabaseAclDefinitionSource::class);
            }

            return $container->get(ConfigAclDefinitionSource::class);
        })->setShared(true);

        $container->add(RoleResolverInterface::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            $resolverClass = $config->get('acl.role_resolver');

            if (is_string($resolverClass) && $resolverClass !== '') {
                /** @var RoleResolverInterface $resolver */
                $resolver = $container->get($resolverClass);

                return $resolver;
            }

            return $container->get(DefaultRoleResolver::class);
        })->setShared(true);

        $container->add(Acl::class, function () use ($container) {
            /** @var AclBuilder $builder */
            $builder = $container->get(AclBuilder::class);

            return $builder->build();
        })->setShared(true);

        $container->add(AclInterface::class, AclGate::class)->setShared(true);
    }
}
