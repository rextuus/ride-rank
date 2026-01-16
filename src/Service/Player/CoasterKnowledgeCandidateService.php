<?php

declare(strict_types=1);

namespace App\Service\Player;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\Player;
use App\Repository\CoasterRepository;
use App\Repository\PairwiseComparisonRepository;
use App\Repository\RiddenCoasterRepository;
use App\Service\Candidate\CandidatePoolProvider;

final readonly class CoasterKnowledgeCandidateService
{
    private const RECENT_MEMORY_LIMIT = 20;
    private const HARD_LIMIT = 200;

    public function __construct(
        private RiddenCoasterRepository $riddenRepository,
        private PairwiseComparisonRepository $comparisonRepository,
        private CandidatePoolProvider $candidatePoolProvider,
    ) {
    }

    public function nextCandidate(Player $player): ?Coaster
    {
        // 1️⃣ Coasters already ridden
        $riddenIds = $this->riddenRepository->findRiddenCoasterIdsForPlayer($player);

        // 2️⃣ Recently shown (fatigue protection)
        $recentIds = $this->comparisonRepository->findRecentCoasterIdsForPlayer($player, self::RECENT_MEMORY_LIMIT);

        $excludedIds = array_unique([...$riddenIds, ...$recentIds]);

        // 3️⃣ Candidate pool (biased but broad)
        $context = $this->buildContext($player);

        $candidates = $this->candidatePoolProvider->buildCandidatePools($player, $context, $excludedIds);

        if (empty($candidates)) {
            return null;
        }

        // 4️⃣ Weighted randomness
        return $this->pickWeighted($candidates);
    }

    private function buildContext(Player $player): array
    {
        $ridden = $this->riddenRepository->findRiddenCoastersForPlayer($player);

        $parkIds = [];
        $countryIds = [];

        foreach ($ridden as $riddenCoaster) {
            $coaster = $riddenCoaster->getCoaster();

            $park = $coaster->getFirstLocationOfType(LocationType::AMUSEMENT_PARK);
            if ($park) {
                $parkIds[$park->getId()] = true;
            }

            $country = $coaster->getFirstLocationOfType(LocationType::COUNTRY);
            if ($country) {
                $countryIds[$country->getId()] = true;
            }
        }

        return [
            'parks' => array_keys($parkIds),
            'countries' => array_keys($countryIds),
        ];
    }

    private function pickWeighted(array $pools): ?Coaster
    {
        // Remove empty pools early
        $pools = array_filter($pools, fn ($p) => !empty($p[0]));

        if (empty($pools)) {
            return null;
        }

        $totalWeight = array_sum(array_column($pools, 1));
        $roll = mt_rand() / mt_getrandmax() * $totalWeight;

        foreach ($pools as [$coasters, $weight]) {
            if ($roll <= $weight) {
                return $coasters[array_rand($coasters)];
            }
            $roll -= $weight;
        }

        return null;
    }
}
