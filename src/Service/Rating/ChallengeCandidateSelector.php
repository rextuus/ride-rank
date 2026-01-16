<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\Player;
use App\Repository\CoasterRepository;
use App\Service\Candidate\CandidatePoolProvider;

final readonly class ChallengeCandidateSelector
{
    public function __construct(
        private CoasterRepository $coasterRepository,
        private SampleScoreCalculator $sampleScoreCalculator,
        private CandidatePoolProvider $candidatePoolProvider
    ) {
    }

    public function selectChallengerCoaster(Coaster $anchor, float $temperature, Player $player): ?Coaster
    {
        $candidates = $this->selectCandidates($player, $anchor);

        if ($candidates === []) {
            return null;
        }

        $scored = [];

        foreach ($candidates as $candidate) {
            $scored[] = [
                'coaster' => $candidate,
                'score' => $this->sampleScoreCalculator->calculateChallengerScore($anchor, $candidate, $player),
            ];
        }

        return $this->sampleScoreCalculator->sampleByScore($scored, $temperature);
    }

    /**
     * @return array<Coaster>
     */
    public function selectCandidates(?Player $player, Coaster $anchor, int $maxCandidates = 50): array
    {
        if ($player === null) {
            // anonymous: purely random global candidates
            $candidates = $this->coasterRepository->findKnowledgeCandidates([], $maxCandidates);
            shuffle($candidates);

            return array_slice($candidates, 0, $maxCandidates);
        }

        // 1️⃣ Build candidate pools based on player experience
        $context = $this->buildContext($player);

        $excludedIds = $context['excludedIds'] ?? [];
        $pools = $this->candidatePoolProvider->buildCandidatePools($player, $context, $excludedIds);

        // 2️⃣ Weighted pick from pools
        $selectedCandidates = [];
        foreach ($pools as [$pool, $weight]) {
            $count = (int) round($weight * $maxCandidates);
            if (empty($pool)) {
                continue;
            }

            shuffle($pool);
            $selectedCandidates = array_merge($selectedCandidates, array_slice($pool, 0, $count));
        }

        // 3️⃣ Remove duplicates & anchor
        $unique = [];
        foreach ($selectedCandidates as $coaster) {
            if ($coaster->getId() === $anchor->getId()) {
                continue;
            }
            $unique[$coaster->getId()] = $coaster;
        }

        // 4️⃣ Hard shuffle & limit
        $final = array_values($unique);
        shuffle($final);
        shuffle($final);

        return array_slice($final, 0, $maxCandidates);
    }

    /**
     * @return array{parks: array<int>, countries: array<int>, excludedIds: array<int>}
     */
    private function buildContext(Player $player): array
    {
        // Parks & countries of already ridden coasters
        $parks = [];
        $countries = [];
        $excludedIds = [];

        $ridden = $player->getRiddenCoasters();
        foreach ($ridden as $riddenCoaster) {
            $coaster = $riddenCoaster->getCoaster();
            $excludedIds[$coaster->getId()] = true;

            $park = $coaster->getFirstLocationOfType(LocationType::AMUSEMENT_PARK);
            if ($park) {
                $parks[$park->getId()] = true;
            }

            $country = $coaster->getFirstLocationOfType(LocationType::COUNTRY);
            if ($country) {
                $countries[$country->getId()] = true;
            }
        }

        return [
            'parks' => array_keys($parks),
            'countries' => array_keys($countries),
            'excludedIds' => array_keys($excludedIds),
        ];
    }
}
