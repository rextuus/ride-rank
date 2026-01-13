<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Entity\Coaster;
use App\Entity\User;
use App\Repository\CoasterRepository;
use App\Repository\PairwiseComparisonRepository;

final class ChallengeCandidateSelector
{
    public function __construct(
        private ChallengeCoasterPoolProvider $poolProvider,
        private PairwiseComparisonRepository $comparisonRepository
    ) {
    }

    /**
     * @return Coaster[]
     */
    public function selectCandidates(
        ?User $user,
        Coaster $anchor,
        int $maxCandidates = 50
    ): array {
        // 1️⃣ Collect pools (biased on purpose)
        $candidates = array_merge(
            $this->poolProvider->getCoasterBySimilarEloRating($anchor),
            $this->poolProvider->getCoasterByLowComparisonCount(),
            $this->poolProvider->getCoasterFromSameCountry($anchor),
            $this->poolProvider->getCoasterByRandomLocationType($anchor),
        );

        // 2️⃣ Remove anchor itself
        $candidates = array_filter(
            $candidates,
            fn (Coaster $c) => $c->getId() !== $anchor->getId()
        );

        // 3️⃣ Remove duplicates (VERY important)
        $unique = [];
        foreach ($candidates as $coaster) {
            $unique[$coaster->getId()] = $coaster;
        }
        $candidates = array_values($unique);

        // 4️⃣ Fatigue: remove recently compared coasters (last N)
        if ($user !== null) {
            $recentCoasters = $this->comparisonRepository
                ->findRecentCoastersForUser($user, 10);

            if (!empty($recentCoasters)) {
                $recentIds = array_map(fn (Coaster $c) => $c->getId(), $recentCoasters);

                $candidates = array_filter(
                    $candidates,
                    fn (Coaster $c) => !in_array($c->getId(), $recentIds, true)
                );
            }
        }

        if (!empty($recentCoasters)) {
            $recentIds = array_map(fn (Coaster $c) => $c->getId(), $recentCoasters);

            $candidates = array_filter(
                $candidates,
                fn (Coaster $c) => !in_array($c->getId(), $recentIds, true)
            );
        }

        // 5️⃣ HARD shuffle to kill ID / alphabet bias
        shuffle($candidates);
        shuffle($candidates);

        // 6️⃣ Limit pool size
        return array_slice($candidates, 0, $maxCandidates);
    }
}

