<?php declare(strict_types=1);

namespace Concept\Extensions\DatabaseEloquent\Console\Commands;

use Concept\Extensions\DatabaseEloquent\Registries\SeederRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DbSeederListCommand extends Command
{
    private const string COMMAND_NAME = 'seeder:list';
    private const string COMMAND_DESCRIPTION = 'Show seeders list';
    private const string MSG_SEEDERS_LIST = 'Seeders List';
    private const string MSG_NOT_FOUND = 'No seeders found.';
    private const string MSG_END_OF_LIST = 'End of seeders list.';

    public function __construct(
        private readonly SeederRegistry $seederRegistry,
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

        /** @var array<string> $seedersList */
        $seedersList = $this->seederRegistry->all();
        if ($seedersList === []) {
            $io->warning(self::MSG_NOT_FOUND);

            return Command::SUCCESS;
        }

        $io->title(self::MSG_SEEDERS_LIST);
        foreach ($seedersList as $seeder) {
            $io->writeln($seeder);
        }

        $io->success(self::MSG_END_OF_LIST);

        return Command::SUCCESS;
    }
}
