<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Commands;

use Concept\Components\AuthAdmin\Models\UserModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserListCommand extends Command
{
    private const string COMMAND_NAME = 'user:list';
    private const string COMMAND_DESCRIPTION = 'Display a list of registered users';

    private const string OPTION_LIMIT = 'limit';
    private const string OPTION_LIMIT_SHORT = 'l';
    private const string OPTION_LIMIT_DESCRIPTION = 'How many users to display?';
    private const int DEFAULT_LIMIT = 10;

    private const string TITLE_USERS_LIST = 'System Users List';
    private const string MSG_NO_USERS = 'No users found in the database.';
    private const string MSG_SHOWING_USERS = 'Showing top %d users.';

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                self::OPTION_LIMIT,
                self::OPTION_LIMIT_SHORT,
                InputOption::VALUE_OPTIONAL,
                self::OPTION_LIMIT_DESCRIPTION,
                self::DEFAULT_LIMIT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = $input->getOption(self::OPTION_LIMIT);
        if (is_numeric($limit)) {
            $limit = (int)$limit;
        } else {
            $limit = self::DEFAULT_LIMIT;
        }

        $io->title(self::TITLE_USERS_LIST);

        /** @var array<array<string, mixed>> $users */
        $users = UserModel::query()
            ->limit($limit)
            ->get()
            ->toArray();

        if (empty($users)) {
            $io->warning(self::MSG_NO_USERS);

            return Command::SUCCESS;
        }

        $header = array_keys($users[0]);
        $io->table($header, $users);

        $io->success(sprintf(self::MSG_SHOWING_USERS, $limit));

        return Command::SUCCESS;
    }
}