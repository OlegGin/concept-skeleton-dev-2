<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent;

use Concept\Extensions\DatabaseEloquent\Contracts\SeederInterface;
use Concept\Extensions\DatabaseEloquent\Registries\SeederRegistry;
use Illuminate\Database\Seeder as IlluminateSeeder;
use Psr\Container\ContainerInterface;

class SeederManager extends IlluminateSeeder
{
    public function __construct(
        private readonly ContainerInterface $appContainer,
        private readonly SeederRegistry $seederRegistry,
    ) {}

    /**
     * @return array<string>
     */
    public function run(): array
    {
        /** @var array<string> $seedersList */
        $seedersList = $this->seederRegistry->all();
        $completed = [];
        if ($seedersList !== []) {
            foreach ($seedersList as $seederClass) {
                $this->resolveAndRun($seederClass);
                $completed[] = $seederClass;
            }
        }

        return $completed;
    }

    public function resolveAndRun(string $class): void
    {
        $seeder = $this->appContainer->get($class);
        if ($seeder instanceof SeederInterface) {
            $seeder->run();
        }
    }
}
