<?php

declare(strict_types=1);

namespace App\Dto\Rcdb;

class TrainData
{
    /**
     * @param LocationData[] $restraints
     * @param TrackElementData[] $builtBy
     */
    public function __construct(
        public readonly ?string $arrangement,
        public readonly array $restraints = [],
        public readonly array $builtBy = [],
    ) {
    }
}
