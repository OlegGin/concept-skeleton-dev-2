<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Contracts;

interface SeederInterface
{
    public function run(): void;
}
