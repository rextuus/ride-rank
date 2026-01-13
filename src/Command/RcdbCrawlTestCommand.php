<?php

namespace App\Command;

use App\Message\CrawlCoasterMessage;
use App\Message\ParseRcdbListPageMessage;
use App\Service\Rcdb\ErrorSummaryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('start', null, InputArgument::REQUIRED )
            ->addArgument('pages', null, InputArgument::REQUIRED)
            ->addOption('no-dry-run', null, InputOption::VALUE_NONE, 'Really persist to database')
            ->addOption('clear-errors', null, InputOption::VALUE_NONE, 'Clear the error summary before starting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = (int) $input->getArgument('start');
        $pages = (int) $input->getArgument('pages');
        for ($id = $start; $id < $start + $pages; $id++) {
            $message = new ParseRcdbListPageMessage($id);
            $this->messageBus->dispatch($message);
        }

        return Command::SUCCESS;

        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('clear-errors')) {
            $this->errorSummary->clear();
            $io->info('Error summary cleared.');
        }
        $start = (int) $input->getOption('start');
        $pages = (int) $input->getOption('limit');
        $dryRun = !$input->getOption('no-dry-run');

        $io->title(sprintf('Triggering crawl for IDs %d to %d', $start, $start + $pages - 1));
        $io->note(sprintf('Dry run: %s', $dryRun ? 'yes' : 'no'));

        for ($id = $start; $id < $start + $pages; $id++) {
            $this->messageBus->dispatch(new CrawlCoasterMessage($id, $dryRun));
            
            if ($id < $start + $pages - 1) {
                $delay = rand(1, 2);
                $io->writeln(sprintf('Waiting %d seconds before next dispatch...', $delay));
                sleep($delay);
            }
        }

        $io->success(sprintf('Dispatched %d messages to the bus', $pages));

        return Command::SUCCESS;
    }
}
