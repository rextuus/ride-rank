<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Category;
use App\Entity\Coaster;
use App\Entity\User;
use App\Enum\OperatingStatus;
use App\Repository\RiddenCoasterRepository;
use App\Repository\UserCoasterAffinityRepository;
use Psr\Log\LoggerInterface;

readonly class ChallengeScoreCalculator
{
    public function __construct(
        private UserCoasterAffinityRepository $userCoasterAffinityRepository,
        private RiddenCoasterRepository $riddenCoasterRepository,
        private LoggerInterface $matchupLogger
    ) {
    }

    public function calculateChallengerScore(
        Coaster $anchor,
        Coaster $candidate,
        ?User $user
    ): float {
        // --- 1. Elo similarity ---
        $eloDelta = abs($anchor->getRating() - $candidate->getRating());
        $eloScale = 200.0;
        $eloScore = exp(-$eloDelta / $eloScale);

        // --- 2. Uncertainty / exploration ---
        $uncertaintyScore = 1.0 / sqrt($candidate->getComparisonsCount() + 1);

        // --- 3. Location similarity (same country) ---
        $locationScore = 0.0;
        $anchorCountry = $anchor->getFirstLocationOfType(LocationType::COUNTRY);
        $candidateCountry = $candidate->getFirstLocationOfType(LocationType::COUNTRY);

        if ($anchorCountry !== null && $candidateCountry !== null) {
            if ($anchorCountry->getId() === $candidateCountry->getId()) {
                $locationScore = 1.0;
            }
        }

        // --- 4. Attribute similarity ---
        $attributeScore = 0.0;

        if ($anchor->getManufacturer() !== null && $candidate->getManufacturer() !== null) {
            if ($anchor->getManufacturer()->getId() === $candidate->getManufacturer()->getId()) {
                $attributeScore += 0.3;
            }
        }

        // Shared categories
        $sharedCategories = array_intersect(
            $this->getCategoryIdsForCoaster($anchor),
            $this->getCategoryIdsForCoaster($candidate)
        );
        if (count($sharedCategories) > 0) {
            $attributeScore += 0.2;
        }

        // --- 5. Random noise ---
        $randomScore = mt_rand() / mt_getrandmax() * 0.15;

        // --- Final weighted score ---
        $baseScore =
            0.45 * $eloScore +
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

        // --- 6. Multiplicative factors ---
        $knowledgeFactor = 1.0;
        $statusFactor = 1.0;
        $riddenFactor = 1.0;

        if ($user !== null) {
            $knowledgeFactor = $this->calculateKnowledgeSymmetryFactor($user, $anchor, $candidate);
            $statusFactor = $this->calculateOperatingStatusFactor($user, $candidate);
            $riddenFactor = $this->calculateRiddenFactor($user, $candidate);
        }

        return $baseScore * $knowledgeFactor * $statusFactor * $riddenFactor;
    }

    private function calculateRiddenFactor(User $user, Coaster $coaster): float
    {
        $ridden = $this->riddenCoasterRepository->findOneBy([
            'user' => $user,
            'coaster' => $coaster
        ]);

        if ($ridden !== null) {
            // User has ridden it → give full weight
            return 1.0;
        }

        // User hasn’t ridden it yet → penalize a bit for “less certainty”
        return 0.8;
    }

    private function getCategoryIdsForCoaster(Coaster $coaster): array
    {
        return array_map(
            function (Category $category) {
                return $category->getId();
            },
            array_filter(
                $coaster->getCategories()->toArray(),
                function (Category $category) {
                    return $category->getIdent() !== 'Roller Coaster';
                }
            )
        );
    }

    public function sampleByScore(array $scoredCandidates, float $temperature = 1.0): Coaster
    {
        // see: softmax sampling
        // Safety: fallback to random
        if (count($scoredCandidates) === 0) {
            $this->matchupLogger->error('No candidates to sample from in sampleByScore');
            throw new \LogicException('No candidates to sample from');
        }

        $this->matchupLogger->debug('Softmax sampling starting', [
            'candidate_count' => count($scoredCandidates),
            'temperature' => $temperature,
        ]);

        // --- Softmax normalization ---
        $weights = [];
        $weightSum = 0.0;

        foreach ($scoredCandidates as $item) {
            $score = $item['score'];

            // Prevent numerical explosions
            $weight = exp($score / $temperature);

            $weights[] = [
                'coaster' => $item['coaster'],
                'weight' => $weight,
            ];

            $this->matchupLogger->debug('Weight calculation', [
                'coaster' => $item['coaster']->getName(),
                'weight' => $weight,
            ]);

            $weightSum += $weight;
        }

        // --- Roulette wheel selection ---
        $random = mt_rand() / mt_getrandmax() * $weightSum;
        $accumulator = 0.0;

        $this->matchupLogger->debug('Roulette wheel roll', [
            'weight_sum' => $weightSum,
            'random_roll' => $random,
        ]);

        foreach ($weights as $item) {
            $accumulator += $item['weight'];

            if ($random <= $accumulator) {
                $selected = $item['coaster'];
                $this->matchupLogger->info('Coaster sampled', [
                    'id' => $selected->getId(),
                    'name' => $selected->getName(),
                    'weight' => $item['weight'],
                    'probability' => $weightSum > 0 ? ($item['weight'] / $weightSum) : 0,
                ]);

                return $selected;
            }
        }

        // Fallback (floating point paranoia)
        $selected = $weights[array_key_last($weights)]['coaster'];
        $this->matchupLogger->warning('Softmax sampling fell back to last item due to floating point precision', [
            'id' => $selected->getId(),
            'name' => $selected->getName(),
        ]);

        return $selected;
    }

    private function userKnowsCoaster(User $user, Coaster $coaster): bool
    {
        $affinity = $this->userCoasterAffinityRepository->findOneBy([
            'user' => $user,
            'coaster' => $coaster,
        ]);

        if (!$affinity) {
            return false;
        }

        if ($affinity->getExposureCount() >= 3) {
            return true;
        }

        if (abs($affinity->getConfidenceScore()) >= 2.0) {
            return true;
        }

        return false;
    }

    private function calculateKnowledgeSymmetryFactor(
        User $user,
        Coaster $anchor,
        Coaster $candidate
    ): float {
        $knowsAnchor = $this->userKnowsCoaster($user, $anchor);
        $knowsCandidate = $this->userKnowsCoaster($user, $candidate);

        if ($knowsAnchor && $knowsCandidate) {
            return 1.0;   // strong comparison
        }

        if ($knowsAnchor || $knowsCandidate) {
            return 0.75;  // learning comparison
        }

        return 0.4;       // weak / guessy
    }

    private function calculateOperatingStatusFactor(
        User $user,
        Coaster $candidate
    ): float {
        if ($candidate->getStatus() === OperatingStatus::OPERATING_SINCE) {
            return 1.0;
        }

        // Removed coaster
        $affinity = $this->userCoasterAffinityRepository->findOneBy([
            'user' => $user,
            'coaster' => $candidate,
        ]);

        // User clearly knows it → let it through
        if ($affinity !== null && $affinity->getExposureCount() >= 3) {
            return 0.9;
        }

        // Expert nostalgia, but rare
        return 0.4;
    }
}
