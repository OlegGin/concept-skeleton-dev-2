<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Console\Commands;

use Concept\Extensions\DatabaseEloquent\Registries\MigrationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DbMigrationPathsCommand extends Command
{
    private const string COMMAND_NAME = 'migration:paths';
    private const string COMMAND_DESCRIPTION = 'Show migrations paths list';
    private const string MSG_MIGRATIONS_LIST = 'Migrations Paths List';
    private const string MSG_NOT_FOUND = 'No migrations found.';
    private const string MSG_END_OF_LIST = 'End of migrations paths list.';

    public function __construct(
        private readonly MigrationRegistry $migrationsRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var array<string> $migrationsPaths */
        $migrationsPaths = $this->migrationsRegistry->all();
        if ($migrationsPaths === []) {
            $io->warning(self::MSG_NOT_FOUND);

            return Command::SUCCESS;
        }

        $io->title(self::MSG_MIGRATIONS_LIST);
        foreach ($migrationsPaths as $migrationPath) {
            $io->writeln($migrationPath);
        }

        $io->success(self::MSG_END_OF_LIST);

        return Command::SUCCESS;
    }
}
