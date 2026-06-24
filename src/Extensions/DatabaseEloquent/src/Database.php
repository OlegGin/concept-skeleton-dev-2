<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent;

use Concept\Extensions\DatabaseEloquent\Contracts\DatabaseInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

class Database implements DatabaseInterface
{
    public function __construct(
        private readonly CapsuleManager $capsule,
    ) {}

    public function capsule(): CapsuleManager
    {
        return $this->capsule;
    }
}
