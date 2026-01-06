<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Entity\Coaster;
use App\Entity\PairwiseComparison;
use App\Entity\RiddenCoaster;
use App\Entity\User;
use App\Entity\UserCoasterAffinity;
use App\Repository\UserCoasterAffinityRepository;
use Doctrine\ORM\EntityManagerInterface;

class EloRatingService
{
    private const BASE_K = 32.0;
    private const CONFIDENCE_WIN = 1.0;
    private const CONFIDENCE_LOSS = -0.5;
    private const CONFIDENCE_MIN = -10.0;
    private const CONFIDENCE_MAX = 10.0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserCoasterAffinityRepository $affinityRepository
    ) {
    }

    /**
     * Updates coaster ratings and user affinity based on a pairwise comparison.
     */
    public function updateRatings(PairwiseComparison $comparison): void
    {
        $winner = $comparison->getWinner();
        $loser = $comparison->getLoser();

        if (!$winner || !$loser) {
            return;
        }

        $ratingWinner = $winner->getRating();
        $ratingLoser = $loser->getRating();

        // Compute expected win probabilities using the Elo formula:
        // EA = 1 / (1 + 10 ^ ((RB - RA) / 400))
        $expectedWinner = 1.0 / (1.0 + 10.0 ** (($ratingLoser - $ratingWinner) / 400.0));
        $expectedLoser = 1.0 / (1.0 + 10.0 ** (($ratingWinner - $ratingLoser) / 400.0));

        // Use a dynamic K-factor:
        // Effective K = BaseK / sqrt(comparisonsCount + 1)
        $user = $comparison->getUser();
        $knowledgeFactor = 1.0;

        if ($user !== null) {
            $knowledgeFactor = $this->calculateKnowledgeFactor($user, $winner, $loser);
        }

        // Use a dynamic K-factor with knowledge scaling (knowledge is not: User have marked coaster as ridden)
        $kWinner = (self::BASE_K / sqrt($winner->getComparisonsCount() + 1)) * $knowledgeFactor;
        $kLoser = (self::BASE_K / sqrt($loser->getComparisonsCount() + 1)) * $knowledgeFactor;


        // Update ratings: R' = R + K * (S - E)
        // For winner, S = 1. For loser, S = 0.
        $newRatingWinner = $ratingWinner + $kWinner * (1.0 - $expectedWinner);
        $newRatingLoser = $ratingLoser + $kLoser * (0.0 - $expectedLoser);

        $winner->setRating($newRatingWinner);
        $winner->setComparisonsCount($winner->getComparisonsCount() + 1);

        $loser->setRating($newRatingLoser);
        $loser->setComparisonsCount($loser->getComparisonsCount() + 1);

        $user = $comparison->getUser();
        if ($user !== null) {
            $this->updateUserAffinity($user, $winner, true);
            $this->updateUserAffinity($user, $loser, false);
        }

        $this->entityManager->persist($winner);
        $this->entityManager->persist($loser);
        $this->entityManager->flush();
    }

    /**
     * Updates or creates UserCoasterAffinity for a user and a coaster.
     */
    private function updateUserAffinity(User $user, Coaster $coaster, bool $isWinner): void
    {
        $affinity = $this->affinityRepository->findOneBy([
            'user' => $user,
            'coaster' => $coaster,
        ]);

        if (!$affinity) {
            $affinity = new UserCoasterAffinity();
            $affinity->setUser($user);
            $affinity->setCoaster($coaster);
        }

        $affinity->setExposureCount($affinity->getExposureCount() + 1);

        if ($isWinner) {
            $affinity->setWinCount($affinity->getWinCount() + 1);
            $newScore = $affinity->getConfidenceScore() + self::CONFIDENCE_WIN;
        } else {
            $affinity->setLossCount($affinity->getLossCount() + 1);
            $newScore = $affinity->getConfidenceScore() + self::CONFIDENCE_LOSS;
        }

        // Clamp confidenceScore to a reasonable range
        $clampedScore = max(self::CONFIDENCE_MIN, min(self::CONFIDENCE_MAX, $newScore));
        $affinity->setConfidenceScore($clampedScore);

        $affinity->setLastSeenAt(new \DateTimeImmutable());

        $this->entityManager->persist($affinity);
    }

    private function userKnowsCoaster(User $user, Coaster $coaster): bool
    {
        // 1️⃣ If user has ridden it, full knowledge
        $ridden = $this->entityManager
            ->getRepository(RiddenCoaster::class)
            ->findOneBy([
                'user' => $user,
                'coaster' => $coaster,
            ]);

        if ($ridden !== null) {
            return true;
        }

        // 2️⃣ Fallback to UserCoasterAffinity
        $affinity = $this->affinityRepository->findOneBy([
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

    private function calculateKnowledgeFactor(User $user, Coaster $winner, Coaster $loser): float
    {
        $knowsWinner = $this->userKnowsCoaster($user, $winner);
        $knowsLoser = $this->userKnowsCoaster($user, $loser);

        if ($knowsWinner && $knowsLoser) {
            return 1.0;
        }

        if ($knowsWinner || $knowsLoser) {
            return 0.6;
        }

        return 0.25;
    }
}
