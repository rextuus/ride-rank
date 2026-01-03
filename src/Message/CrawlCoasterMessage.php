<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class CrawlCoasterMessage
{
    public function __construct(
        public int $rcdbId,
        public bool $dryRun = false,
    ) {
    }

    public function getRcdbId(): int
    {
        return $this->rcdbId;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }
}
