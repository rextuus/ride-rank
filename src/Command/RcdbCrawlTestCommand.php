<?php

namespace App\Command;

use App\Message\CrawlCoasterMessage;
use App\Service\Rcdb\ErrorSummaryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:rcdb:crawl-test',
    description: 'Triggers crawling for a range of rcdb entries asynchronously to test parsing',
)]
class RcdbCrawlTestCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ErrorSummaryService $errorSummary,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Start RCDB ID', 1)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of entries to crawl', 1000)
            ->addOption('no-dry-run', null, InputOption::VALUE_NONE, 'Really persist to database')
            ->addOption('clear-errors', null, InputOption::VALUE_NONE, 'Clear the error summary before starting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('clear-errors')) {
            $this->errorSummary->clear();
            $io->info('Error summary cleared.');
        }
        $start = (int) $input->getOption('start');
        $limit = (int) $input->getOption('limit');
        $dryRun = !$input->getOption('no-dry-run');

        $io->title(sprintf('Triggering crawl for IDs %d to %d', $start, $start + $limit - 1));
        $io->note(sprintf('Dry run: %s', $dryRun ? 'yes' : 'no'));

        for ($id = $start; $id < $start + $limit; $id++) {
            $this->messageBus->dispatch(new CrawlCoasterMessage($id, $dryRun));
            
            if ($id < $start + $limit - 1) {
                $delay = rand(1, 2);
                $io->writeln(sprintf('Waiting %d seconds before next dispatch...', $delay));
                sleep($delay);
            }
        }

        $io->success(sprintf('Dispatched %d messages to the bus', $limit));

        return Command::SUCCESS;
    }
}
