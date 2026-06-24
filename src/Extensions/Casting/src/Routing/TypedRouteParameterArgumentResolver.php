<?php declare(strict_types=1);

namespace Concept\Extensions\Casting\Routing;

use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Extensions\Casting\Contracts\CasterInterface;
use Concept\Extensions\Casting\Exceptions\CastingException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;

final class TypedRouteParameterArgumentResolver implements ArgumentResolverInterface
{
    public function __construct(private readonly CasterInterface $caster) {}

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

        return $this->caster->cast($vars[$parameter->getName()], $type->getName());
    }
}
