<?php declare(strict_types=1);

namespace Concept\Extensions\CastingValinor;

use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\CastingValinor\Exceptions\CastingException;
use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\Cache\FileWatchingCache;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;

final class Caster implements CasterInterface
{
    private TreeMapper $mapper;

    /**
     * @param list<class-string> $transformerClasses
     */
    public function __construct(
        string $cacheDirectory,
        array $transformerClasses = [],
        bool $debug = false,
    ) {
        $cache = new FileSystemCache($cacheDirectory);
        if ($debug) {
            $cache = new FileWatchingCache($cache);
        }

        $builder = (new MapperBuilder())
            ->withCache($cache)
            ->allowScalarValueCasting()
            ->allowSuperfluousKeys();

        foreach ($transformerClasses as $transformerClass) {
            $builder = $builder->registerConverter($transformerClass);
        }

        $this->mapper = $builder->mapper();
    }

    public function cast(mixed $value, string $type): mixed
    {
        try {
            return $this->mapper->map($type, $value);
        } catch (MappingError $e) {
            throw new CastingException($type, $e);
        }
    }
}
