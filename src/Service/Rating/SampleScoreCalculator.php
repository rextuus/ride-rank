<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Category;
use App\Entity\Coaster;
use App\Entity\Player;
use App\Enum\OperatingStatus;
use App\Repository\CoasterRepository;
use App\Repository\PlayerCoasterAffinityRepository;
use App\Repository\RiddenCoasterRepository;
use App\Service\Player\PlayerExperienceLevel;
use Psr\Log\LoggerInterface;

readonly class SampleScoreCalculator
{
    public function __construct(
        private PlayerCoasterAffinityRepository $playerCoasterAffinityRepository,
        private RiddenCoasterRepository $riddenCoasterRepository,
        private LoggerInterface $logger,
        private PlayerCoasterAffinityRepository $playerAffinityRepository,
        private CoasterRepository $coasterRepository,
    ) {
    }

    public function calculateAnchorScore(Coaster $candidate, Player $player): float
    {
        $score = 1.0;

        $affinity = $this->playerAffinityRepository->findOneBy(['player' => $player, 'coaster' => $candidate]);

        if ($affinity !== null) {
            $score += abs($affinity->getConfidenceScore());
            $score += min(2.0, $affinity->getExposureCount() * 0.3);
        }

        // Favor well-established coasters
        $score += log($candidate->getComparisonsCount() + 1) * 0.25;

        // Penalize recently seen coasters (cold-start prevention / diversity)
        $recent = $this->coasterRepository->findRecentForPlayer($player, 20); // last 10 duels
        $recentIds = array_map(fn($c) => $c->getId(), $recent);

        if (in_array($candidate->getId(), $recentIds, true)) {
            $score *= 0.05; // reduce score for recently seen coasters
        }

        return $score;
    }


    /**
     * Calculates a score for a candidate coaster against an anchor coaster,
     * taking into account player knowledge, ridden history, and experience.
     */
    public function calculateChallengerScore(
        Coaster $anchor,
        Coaster $candidate,
        Player $player
    ): float {
        // --- 1️⃣ Elo similarity ---
        $eloDelta = abs($anchor->getRating() - $candidate->getRating());
        $eloScore = exp(-$eloDelta / 200.0);

        // --- 2️⃣ Uncertainty / exploration ---
        $uncertaintyScore = 1.0 / sqrt($candidate->getComparisonsCount() + 1);

        // --- 3️⃣ Location similarity ---
        $anchorCountry = $anchor->getFirstLocationOfType(LocationType::COUNTRY);
        $candidateCountry = $candidate->getFirstLocationOfType(LocationType::COUNTRY);
        $locationScore = ($anchorCountry && $candidateCountry && $anchorCountry->getId() === $candidateCountry->getId()) ? 1.0 : 0.0;

        // --- 4️⃣ Attribute similarity ---
        $attributeScore = 0.0;

        if ($anchor->getManufacturer()?->getId() === $candidate->getManufacturer()?->getId()) {
            $attributeScore += 0.3;
        }

        $sharedCategories = array_intersect(
            $this->getCategoryIdsForCoaster($anchor),
            $this->getCategoryIdsForCoaster($candidate)
        );
        if ($sharedCategories) {
            $attributeScore += 0.2;
        }

        // --- 5️⃣ Random noise ---
        $randomScore = mt_rand() / mt_getrandmax() * 0.15;

        // --- Base weighted score ---
        $baseScore = 0.45 * $eloScore +
            0.25 * $uncertaintyScore +
            0.15 * $locationScore +
            0.10 * $attributeScore +
            0.05 * $randomScore;

        //        $baseScore =
//            0.96 * $eloScore +
//            0.01 * $uncertaintyScore +
//            0.01 * $locationScore +
//            0.01 * $attributeScore +
//            0.01 * $randomScore;

        // --- 6️⃣ Multiplicative factors ---
        $knowledgeFactor = $this->calculateKnowledgeSymmetryFactor($player, $anchor, $candidate);
        $statusFactor = $this->calculateOperatingStatusFactor($player, $candidate);
        $riddenFactor = $this->calculateRiddenFactor($player, $candidate);

        return $baseScore * $knowledgeFactor * $statusFactor * $riddenFactor;
    }

    /**
     * Returns 1.0 if player has ridden the coaster, else 0.8
     */
    private function calculateRiddenFactor(Player $player, Coaster $coaster): float
    {
        $ridden = $this->riddenCoasterRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        return $ridden !== null ? 1.0 : 0.8;
    }

    /**
     * Returns true if player is sufficiently familiar with the coaster
     */
    private function playerKnowsCoaster(Player $player, Coaster $coaster): bool
    {
        $affinity = $this->playerCoasterAffinityRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if ($affinity === null) {
            return false;
        }

        return $affinity->getExposureCount() >= 3 || abs($affinity->getConfidenceScore()) >= 2.0;
    }

    /**
     * Knowledge symmetry factor between anchor and candidate
     */
    private function calculateKnowledgeSymmetryFactor(Player $player, Coaster $anchor, Coaster $candidate): float
    {
        $knowsAnchor = $this->playerKnowsCoaster($player, $anchor);
        $knowsCandidate = $this->playerKnowsCoaster($player, $candidate);

        return match (true) {
            $knowsAnchor && $knowsCandidate => 1.0,
            $knowsAnchor || $knowsCandidate => 0.75,
            default => 0.4,
        };
    }

    /**
     * Factor based on operating status of the coaster
     */
    private function calculateOperatingStatusFactor(Player $player, Coaster $candidate): float
    {
        if ($candidate->getStatus() === OperatingStatus::OPERATING_SINCE) {
            return 1.0;
        }

        $affinity = $this->playerCoasterAffinityRepository->findOneBy([
            'player' => $player,
            'coaster' => $candidate,
        ]);

        if ($affinity !== null && $affinity->getExposureCount() >= 3) {
            return 0.9;
        }

        return 0.4;
    }

    /**
     * Helper: returns IDs of categories excluding "Roller Coaster"
     */
    private function getCategoryIdsForCoaster(Coaster $coaster): array
    {
        return array_map(
            static fn(Category $category) => $category->getId(),
            array_filter(
                $coaster->getCategories()->toArray(),
                static fn(Category $category) => $category->getIdent() !== 'Roller Coaster'
            )
        );
    }

    /**
     * Sample a coaster based on a scored candidate array using softmax-like weighting.
     *
     * @param array<int, array{coaster: Coaster, score: float}> $scoredCandidates
     */
    public function sampleByScore(array $scoredCandidates, float $temperature = 1.0): Coaster
    {
        if ($scoredCandidates === []) {
            $this->logger->error('No candidates to sample from');
            throw new \LogicException('No candidates to sample from');
        }

        $weights = [];
        $weightSum = 0.0;

        foreach ($scoredCandidates as $item) {
            $weight = exp($item['score'] / $temperature);
            $weights[] = ['coaster' => $item['coaster'], 'weight' => $weight];
            $weightSum += $weight;

//            if ($item['coaster']->getIdent() === 'Schweizer Bobbahn'){
//                dd($item);
//            }
        }

        $random = mt_rand() / mt_getrandmax() * $weightSum;
        $accumulator = 0.0;

        foreach ($weights as $item) {
            $accumulator += $item['weight'];
            if ($random <= $accumulator) {
                return $item['coaster'];
            }
        }

        // fallback
        return $weights[array_key_last($weights)]['coaster'];
    }
}
