<?php

declare(strict_types=1);

namespace App\Dto\Rcdb;

class TrackData
{
    /**
     * @param TrackElementData[] $elements
     */
    public function __construct(
        public readonly ?float $length,
        public readonly ?float $height,
        public readonly ?float $drop,
        public readonly ?float $speed,
        public readonly ?int $inversions,
        public readonly ?int $duration,
        public readonly ?int $verticalAngle,
        public readonly array $elements = [],
    ) {
    }
}
