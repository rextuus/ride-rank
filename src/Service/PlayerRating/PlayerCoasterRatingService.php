<?php

namespace App\Service\PlayerRating;

use App\Entity\Coaster;
use App\Entity\PairwiseComparison;
use App\Entity\Player;
use App\Entity\PlayerCoasterRating;
use App\Enum\ComparisonOutcome;
use App\Repository\PlayerCoasterRatingRepository;
use Doctrine\ORM\EntityManagerInterface;

class PlayerCoasterRatingService
{
    private const DEFAULT_RATING = 1200.0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerCoasterRatingRepository $playerCoasterRatingRepository,
    ) {
    }

    public function applyComparison(PairwiseComparison $comparison): void
    {
        $player = $comparison->getPlayer(); // assuming you added a player relation

        // skipped
        if ($comparison->getOutcome() === ComparisonOutcome::SKIP){
            $coasterA = $comparison->getCoasterA();
            $coasterB  = $comparison->getCoasterB();

            $coasterARating = $this->getOrCreate($player, $coasterA);
            $coasterBRating  = $this->getOrCreate($player, $coasterB);

            $coasterARating->incrementPresented();
            $coasterBRating->incrementPresented();

            $coasterARating->incrementSkipped();
            $coasterBRating->incrementSkipped();

            $this->entityManager->persist($coasterARating);
            $this->entityManager->persist($coasterBRating);
            $this->entityManager->flush();

            return;
        }

        $winner = $comparison->getWinner();
        $loser  = $comparison->getLoser();

        $winnerRating = $this->getOrCreate($player, $winner);
        $loserRating  = $this->getOrCreate($player, $loser);

        $this->updateRatings($winnerRating, $loserRating);

        $winnerRating->incrementPresented();
        $loserRating->incrementPresented();

        $this->entityManager->persist($winnerRating);
        $this->entityManager->persist($loserRating);
        $this->entityManager->flush();
    }

    private function getOrCreate(Player $player, Coaster $coaster): PlayerCoasterRating
    {
        $rating = $this->playerCoasterRatingRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if ($rating) {
            return $rating;
        }

        $rating = new PlayerCoasterRating($player, $coaster);
        $rating->setRating(self::DEFAULT_RATING);

        $this->entityManager->persist($rating);

        return $rating;
    }

    private function updateRatings(
        PlayerCoasterRating $winner,
        PlayerCoasterRating $loser
    ): void {
        $expectedWinner = $this->expectedScore(
            $winner->getRating(),
            $loser->getRating()
        );

        $expectedLoser = 1.0 - $expectedWinner;

        $k = $this->kFactor($winner->getGamesPlayed(), $loser->getGamesPlayed());

        $winner->setRating($winner->getRating() + $k * (1 - $expectedWinner));
        $loser->setRating($loser->getRating() + $k * (0 - $expectedLoser));

        $winner->incrementGamesPlayed();
        $winner->incrementWins();

        $loser->incrementGamesPlayed();
        $loser->incrementLosses();
    }

    private function expectedScore(float $ratingA, float $ratingB): float
    {
        return 1 / (1 + 10 ** (($ratingB - $ratingA) / 400));
    }

    private function kFactor(int $gamesA, int $gamesB): int
    {
        $minimumGames = min($gamesA, $gamesB);

        return match (true) {
            $minimumGames < 10 => 40,
            $minimumGames < 30 => 24,
            default => 16,
        };
    }
}
