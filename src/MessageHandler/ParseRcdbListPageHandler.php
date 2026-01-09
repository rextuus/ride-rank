<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CrawlCoasterMessage;
use App\Message\ParseRcdbListPageMessage;
use App\Service\Rcdb\RcdbListCrawler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ParseRcdbListPageHandler
{
    public function __construct(
        private RcdbListCrawler $listCrawler,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(ParseRcdbListPageMessage $message): void
    {
        $url = $message->getUrl();

        $this->logger->info('Parsing RCDB list page', [
            'page' => $message->page,
            'url'  => $url,
        ]);

        $result = $this->listCrawler->fetchIdsFromList($url);

        $coasters = $result['coasters'];
        $parks    = $result['parks'];

        // ⬇⬇⬇ USE THE RESULT HERE ⬇⬇⬇

        // Example 1: Log counts
        $this->logger->info('RCDB page parsed', [
            'page'         => $message->page,
            'coasterCount' => count($coasters),
            'parkCount'    => count($parks),
        ]);

        foreach ($coasters as $coasterId) {
            $this->messageBus->dispatch(new CrawlCoasterMessage($coasterId));
        }

        // Example 2: Dispatch follow-up messages
        // foreach ($coasters as $coasterId) {
        //     $this->bus->dispatch(new CrawlCoasterMessage($coasterId));
        // }

        // Example 3: Persist to DB
        // $this->repository->storePageResult(
        //     $message->page,
        //     $coasters,
        //     $parks
        // );
    }
}
