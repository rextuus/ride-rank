<?php

declare(strict_types=1);

namespace App\Dto\Rcdb;

class TrackElementData
{
    public function __construct(
        public readonly string $ident,
        public readonly ?int $id,
        public readonly string $url,
    ) {
    }
}
