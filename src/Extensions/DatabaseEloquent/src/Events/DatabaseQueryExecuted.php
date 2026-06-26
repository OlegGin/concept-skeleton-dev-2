<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Events;

final readonly class DatabaseQueryExecuted
{
    /**
     * @param array<mixed> $bindings
     */
    public function __construct(
        public string $sql,
        public string $rawSql,
        public array $bindings,
        public float $time,
        public string $connectionName,
    ) {}
}
