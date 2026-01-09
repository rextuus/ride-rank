<?php

namespace App\MessageHandler;

use App\Entity\Park;
use App\Entity\UnprocessableEntry;
use App\Message\CrawlCoasterMessage;
use App\Repository\ParkRepository;
use App\Repository\UnprocessableEntryRepository;
use App\Service\Rcdb\Crawler;
use App\Service\Rcdb\ErrorSummaryService;
use App\Service\Rcdb\Exception\IsNeitherCoasterNorParcEntryException;
use App\Service\Rcdb\Exception\IsParcEntryException;
use App\Service\Rcdb\Exception\RcdbException;
use App\Service\Rcdb\ImportService;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
        private EntityManagerInterface $entityManager,
        private ParkRepository $parkRepository,
        private UnprocessableEntryRepository $unprocessableEntryRepository,
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

            if (!$message->isDryRun()) {
                $entry = $this->unprocessableEntryRepository->findOneBy(['rcdbId' => $id]);
                if ($entry) {
                    $entry->setReprocessed(true);
                    $this->entityManager->persist($entry);
                    $this->entityManager->flush();
                }
            }

            $this->logger->info(sprintf('Successfully crawled coaster ID %d (Dry run: %s)', $id, $message->isDryRun() ? 'yes' : 'no'));
        } catch (IsParcEntryException $e) {
            if (!$message->isDryRun()) {
                $park = $this->parkRepository->findOneBy(['rcdbId' => $id]);
                if (!$park) {
                    $park = new Park();
                    $park->setRcdbId($id);
                }
                $park->setName($e->getParkName());
                $park->setIdent($e->getParkName());
                $this->entityManager->persist($park);
                $this->entityManager->flush();
            }
        } catch (IsNeitherCoasterNorParcEntryException|LockWaitTimeoutException $e) {
            if (!$message->isDryRun()) {
                $entry = $this->unprocessableEntryRepository->findOneBy(['rcdbId' => $id]);
                if (!$entry) {
                    $entry = new UnprocessableEntry();
                    $entry->setRcdbId($id);
                }
                $entry->setReprocessed(false);
                $this->entityManager->persist($entry);
                $this->entityManager->flush();
            }
        }catch (RcdbException|Exception $e) {
            dd($e);
        }
    }
}
