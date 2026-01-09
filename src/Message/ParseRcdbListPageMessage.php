<?php

declare(strict_types=1);

namespace App\Message;

final readonly class ParseRcdbListPageMessage
{
    public function __construct(
        public int $page,
        public int $status = 93,
        public int $objectType = 2
    ) {
    }

    public function getUrl(): string
    {
        return sprintf(
            '/r.htm?page=%d&st=%d&ot=%d',
            $this->page,
            $this->status,
            $this->objectType
        );
    }
}
