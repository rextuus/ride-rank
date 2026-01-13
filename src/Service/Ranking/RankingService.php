<?php

declare(strict_types=1);

namespace App\Service\Ranking;

use App\Repository\CoasterRepository;
use App\Repository\PairwiseComparisonRepository;

class RankingService
{

    public function __construct(private CoasterRepository $coasterRepository)
    {

    }

    public function getPersonalRankingForUser(RankingFilter $filter): array
    {
        return $this->coasterRepository->calculateRankingByFilter($filter);
    }
}
