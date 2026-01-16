<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Entity\Coaster;
use App\Entity\PairwiseComparison;
use App\Entity\Player;
use App\Entity\PlayerCoasterAffinity;
use App\Entity\RiddenCoaster;
use App\Entity\PlayerCoasterRating;
use App\Enum\ComparisonOutcome;
use App\Repository\PlayerCoasterAffinityRepository;
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
        private readonly PlayerCoasterAffinityRepository $affinityRepository
    ) {
    }

    /**
     * Updates player-specific coaster ratings and affinities based on a comparison.
     */
    public function applyComparison(PairwiseComparison $comparison): void
    {
        if ($comparison->getOutcome() === ComparisonOutcome::SKIP){
            return;
        }

        $winner = $comparison->getWinner();
        $loser = $comparison->getLoser();
        $player = $comparison->getPlayer();

//        // --- Get or create player-specific ratings ---
//        $winnerRating = $this->getOrCreateRating($player, $winner);
//        $loserRating  = $this->getOrCreateRating($player, $loser);
//
//        // --- Calculate expected score for ELO ---
//        $expectedWinner = 1 / (1 + 10 ** (($loserRating->getRating() - $winnerRating->getRating()) / 400));
//        $expectedLoser  = 1 / (1 + 10 ** (($winnerRating->getRating() - $loserRating->getRating()) / 400));
//
//        $knowledgeFactor = $this->calculateKnowledgeFactor($player, $winner, $loser);
//
//        $kWinner = (self::BASE_K / sqrt($winnerRating->getGamesPlayed() + 1)) * $knowledgeFactor;
//        $kLoser  = (self::BASE_K / sqrt($loserRating->getGamesPlayed() + 1)) * $knowledgeFactor;

        // --- Update player-specific ratings ---
//        $winnerRating->setRating($winnerRating->getRating() + $kWinner * (1 - $expectedWinner));
//        $loserRating->setRating($loserRating->getRating() + $kLoser * (0 - $expectedLoser));
//        $winnerRating->incrementGamesPlayed();
//        $winnerRating->incrementWins();
//        $loserRating->incrementGamesPlayed();
//        $loserRating->incrementLosses();

        // --- Update global Coaster stats (anchor selection relies on this) ---
        $knowledgeFactor = $this->calculateKnowledgeFactor($player, $winner, $loser);

        $winner->setComparisonsCount($winner->getComparisonsCount() + 1);
        $loser->setComparisonsCount($loser->getComparisonsCount() + 1);

        // Simple global ELO adjustment: we can use the same formula, but based on global rating
        $globalExpectedWinner = 1 / (1 + 10 ** (($loser->getRating() - $winner->getRating()) / 400));
        $globalExpectedLoser = 1 / (1 + 10 ** (($winner->getRating() - $loser->getRating()) / 400));

        $kWinner = (self::BASE_K / sqrt($winner->getComparisonsCount() + 1)) * $knowledgeFactor;
        $kLoser = (self::BASE_K / sqrt($loser->getComparisonsCount() + 1)) * $knowledgeFactor;

        $winner->setRating($winner->getRating() + $kWinner * (1 - $globalExpectedWinner));
        $loser->setRating($loser->getRating() + $kLoser * (0 - $globalExpectedLoser));

        // --- Update player affinity ---
        $this->updatePlayerAffinity($player, $winner, true);
        $this->updatePlayerAffinity($player, $loser, false);

        // --- Persist everything ---
//        $this->entityManager->persist($winnerRating);
//        $this->entityManager->persist($loserRating);
        $this->entityManager->persist($winner);
        $this->entityManager->persist($loser);
        $this->entityManager->flush();
    }

    private function getOrCreateRating(Player $player, Coaster $coaster): PlayerCoasterRating
    {
        $ratingRepo = $this->entityManager->getRepository(PlayerCoasterRating::class);
        $rating = $ratingRepo->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if ($rating) {
            return $rating;
        }

        $rating = new PlayerCoasterRating($player, $coaster);
        $this->entityManager->persist($rating);

        return $rating;
    }

    private function updatePlayerAffinity(Player $player, Coaster $coaster, bool $isWinner): void
    {
        $affinity = $this->affinityRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if (!$affinity) {
            $affinity = new PlayerCoasterAffinity($player, $coaster);
        }

        $affinity->setExposureCount($affinity->getExposureCount() + 1);

        if ($isWinner) {
            $affinity->setWinCount($affinity->getWinCount() + 1);
            $newScore = $affinity->getConfidenceScore() + self::CONFIDENCE_WIN;
        } else {
            $affinity->setLossCount($affinity->getLossCount() + 1);
            $newScore = $affinity->getConfidenceScore() + self::CONFIDENCE_LOSS;
        }

        $affinity->setConfidenceScore(max(self::CONFIDENCE_MIN, min(self::CONFIDENCE_MAX, $newScore)));
        $affinity->setLastSeenAt(new \DateTimeImmutable());

        $this->entityManager->persist($affinity);
    }

    private function playerKnowsCoaster(Player $player, Coaster $coaster): bool
    {
        $ridden = $this->entityManager
            ->getRepository(RiddenCoaster::class)
            ->findOneBy([
                'player' => $player,
                'coaster' => $coaster,
            ]);

        if ($ridden !== null) {
            return true;
        }

        $affinity = $this->affinityRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if (!$affinity) {
            return false;
        }

        return $affinity->getExposureCount() >= 3 || abs($affinity->getConfidenceScore()) >= 2.0;
    }

    private function calculateKnowledgeFactor(Player $player, Coaster $winner, Coaster $loser): float
    {
        $knowsWinner = $this->playerKnowsCoaster($player, $winner);
        $knowsLoser = $this->playerKnowsCoaster($player, $loser);

        if ($knowsWinner && $knowsLoser) {
            return 1.0;
        }

        if ($knowsWinner || $knowsLoser) {
            return 0.6;
        }

        return 0.25;
    }
}
