<?php

declare(strict_types=1);

namespace App\Service\Statistic;

final class CoasterPrimaryStats
{
    public function __construct(
        public int $comparisonsTotal,
        public int $comparisonsLast24h,
        public int $comparisonsLast7d,
        public int $uniquePlayers,
        public float $rating,
        public float $winRate,
        public float $ratingDeviation,
        public float $confidenceScore,
        public int $globalRank,
        public float $rankPercentile,
    ) {}
}

