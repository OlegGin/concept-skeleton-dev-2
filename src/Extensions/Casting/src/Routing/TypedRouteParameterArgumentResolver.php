<?php declare(strict_types=1);

namespace Concept\Extensions\Casting\Routing;

use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Extensions\Casting\Contracts\CasterInterface;
use Concept\Extensions\Casting\Exceptions\CastingException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

final class TypedRouteParameterArgumentResolver implements ArgumentResolverInterface
{
    private const string ERR_CASTER_NOT_REGISTERED = 'CasterInterface is not registered in the container.';

    private ?CasterInterface $caster = null;

    public function __construct(private readonly ContainerInterface $container) {}

    public function supports(ReflectionParameter $parameter, array $vars): bool
    {
        if (!array_key_exists($parameter->getName(), $vars)) {
            return false;
        }

        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        if ($type->isBuiltin()) {
            return true;
        }

        $typeName = $type->getName();

        return $typeName !== ServerRequestInterface::class
            && !is_subclass_of($typeName, ServerRequestInterface::class);
    }

    public function resolve(ReflectionParameter $parameter, ServerRequestInterface $request, array $vars): mixed
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            throw new CastingException('mixed');
        }

        return $this->caster()->cast($vars[$parameter->getName()], $type->getName());
    }

    private function caster(): CasterInterface
    {
        if ($this->caster === null) {
            $caster = $this->container->get(CasterInterface::class);
            if (!$caster instanceof CasterInterface) {
                throw new RuntimeException(self::ERR_CASTER_NOT_REGISTERED);
            }

            $this->caster = $caster;
        }

        return $this->caster;
    }
}
