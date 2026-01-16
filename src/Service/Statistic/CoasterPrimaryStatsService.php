<?php

declare(strict_types=1);

namespace App\Service\Statistic;

use App\Entity\Coaster;
use App\Repository\PairwiseComparisonRepository;
use App\Repository\CoasterRepository;

final class CoasterPrimaryStatsService
{
    public function __construct(
        private PairwiseComparisonRepository $comparisonRepository,
        private CoasterRepository $coasterRepository,
    ) {}

    public function getStats(Coaster $coaster): array
    {
        $raw = $this->comparisonRepository->getPrimaryStatsForCoaster($coaster);

        $comparisonsTotal = (int) ($raw['total_comparisons'] ?? 0);

        $ratingDeviation = max(
            50,
            400 / sqrt($comparisonsTotal + 1)
        );

        $confidenceScore = min(
            1.0,
            log10($comparisonsTotal + 1) / 2
        );

        $globalRank = $this->coasterRepository->getGlobalRank($coaster);

        return [
            'comparisonsTotal' => $comparisonsTotal,
            'comparisonsLast24h' => (int) ($raw['last_24h'] ?? 0),
            'comparisonsLast7d' => (int) ($raw['last_7d'] ?? 0),
            'uniquePlayers' => (int) ($raw['unique_players'] ?? 0),
            'winRate' => round((float) ($raw['win_rate'] ?? 0) * 100, 2),
            'rating' => $coaster->getRating(),
            'ratingDeviation' => round($ratingDeviation, 1),
            'confidenceScore' => round($confidenceScore, 2),
            'globalRank' => $globalRank,
        ];
    }
}
