<?php

namespace App\MessageHandler;

use App\Message\CrawlCoasterMessage;
use App\Service\Rcdb\Crawler;
use App\Service\Rcdb\ErrorSummaryService;
use App\Service\Rcdb\ImportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CrawlCoasterMessageHandler
{
    public function __construct(
        private Crawler $crawler,
        private ImportService $importService,
        private LoggerInterface $logger,
        private ErrorSummaryService $errorSummary,
        #[Autowire('%kernel.project_dir%/var/rcdb_dump')]
        private string $dumpDir,
    ) {
    }

    public function __invoke(CrawlCoasterMessage $message): void
    {
        $id = $message->getRcdbId();

        try {
            $rawValues = $this->crawler->fetchRollerCoaster($id);
            
            // Store parsed output
            if (!is_dir($this->dumpDir)) {
                mkdir($this->dumpDir, 0777, true);
            }
            file_put_contents(
                sprintf('%s/%d.json', $this->dumpDir, $id),
                json_encode($rawValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            $this->importService->importFromCrawlerArray($id, $rawValues, $message->isDryRun());

            $this->logger->info(sprintf('Successfully crawled coaster ID %d (Dry run: %s)', $id, $message->isDryRun() ? 'yes' : 'no'));
        } catch (\Exception $e) {
            $this->errorSummary->logError($id, $e->getMessage());
            $this->logger->error(sprintf('Failed to crawl coaster ID %d: %s', $id, $e->getMessage()));
        }
    }
}
