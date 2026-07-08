<?php declare(strict_types=1);

namespace Concept\Components\Health\Commands;

use Concept\Components\Health\Contracts\HealthCheckInterface;
use Concept\Components\Health\HealthStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class AppHealthCommand extends Command
{
    private const string COMMAND_NAME = 'app:health';
    private const string COMMAND_DESCRIPTION = 'Run application health checks (boot smoke)';
    private const string OPTION_STRICT = 'strict';
    private const string OPTION_STRICT_DESCRIPTION = 'Treat warnings as failures (exit 1)';
    private const string TITLE = 'App Health';

    /**
     * @param list<HealthCheckInterface> $checks
     */
    public function __construct(
        private readonly array $checks,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                self::OPTION_STRICT,
                null,
                InputOption::VALUE_NONE,
                self::OPTION_STRICT_DESCRIPTION,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(self::TITLE);

        $strict = (bool) $input->getOption(self::OPTION_STRICT);
        $counts = [
            HealthStatus::Ok->value => 0,
            HealthStatus::Warn->value => 0,
            HealthStatus::Fail->value => 0,
            HealthStatus::Skip->value => 0,
        ];

        $failed = false;
        $warned = false;

        foreach ($this->checks as $check) {
            $result = $check->run();
            $counts[$result->status->value]++;

            $label = str_pad($check->name(), 16);
            $tag = strtoupper($result->status->value);

            match ($result->status) {
                HealthStatus::Ok => $io->writeln(sprintf(' <info>[OK]</info>   %s %s', $label, $result->message)),
                HealthStatus::Warn => $io->writeln(sprintf(' <comment>[WARN]</comment> %s %s', $label, $result->message)),
                HealthStatus::Fail => $io->writeln(sprintf(' <error>[FAIL]</error> %s %s', $label, $result->message)),
                HealthStatus::Skip => $io->writeln(sprintf(' <fg=gray>[SKIP]</> %s %s', $label, $result->message)),
            };

            if ($result->status === HealthStatus::Fail) {
                $failed = true;
            }
            if ($result->status === HealthStatus::Warn) {
                $warned = true;
            }
        }

        $io->newLine();
        $io->writeln(sprintf(
            ' %d ok, %d warn, %d fail, %d skip',
            $counts[HealthStatus::Ok->value],
            $counts[HealthStatus::Warn->value],
            $counts[HealthStatus::Fail->value],
            $counts[HealthStatus::Skip->value],
        ));

        if ($failed || ($strict && $warned)) {
            $io->error('Health check failed.');

            return Command::FAILURE;
        }

        if ($warned) {
            $io->warning('Health check completed with warnings.');

            return Command::SUCCESS;
        }

        $io->success('Health check passed.');

        return Command::SUCCESS;
    }
}
