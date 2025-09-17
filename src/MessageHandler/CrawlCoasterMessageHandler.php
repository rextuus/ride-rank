<?php

namespace App\MessageHandler;

use App\Message\CrawlCoasterMessage;
use App\Service\Rcdb\Crawler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final readonly class CrawlCoasterMessageHandler
{
    public function __construct(private Crawler $crawler, private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function __invoke(CrawlCoasterMessage $message): void
    {
        $id = $message->getRcdbId();

        $rawValues = $this->crawler->fetchRollerCoaster($id);

        $nextId = $id + 1;

        $this->eventDispatcher->dispatch(new CrawlCoasterMessage($nextId));
    }
}
