<?php

namespace App\Service\Ranking;

use App\Entity\Coaster;
use App\Entity\PairwiseComparison;
use App\Entity\User;
use App\Entity\UserCoasterRating;
use App\Repository\UserCoasterRatingRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserCoasterRatingService
{
    private const DEFAULT_RATING = 1200.0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserCoasterRatingRepository $userCoasterRatingRepository,
    ) {
    }

    public function applyComparison(PairwiseComparison $comparison): void
    {
        $user = $comparison->getUser();

        if ($user === null) {
            return;
        }

        $winner = $comparison->getWinner();
        $loser  = $comparison->getLoser();

        $winnerRating = $this->getOrCreate($user, $winner);
        $loserRating  = $this->getOrCreate($user, $loser);

        $this->updateRatings($winnerRating, $loserRating);

        $this->entityManager->persist($winnerRating);
        $this->entityManager->persist($loserRating);
        $this->entityManager->flush();
    }

    private function getOrCreate(User $user, Coaster $coaster): UserCoasterRating
    {
        $rating = $this->userCoasterRatingRepository->findOneBy([
            'user' => $user,
            'coaster' => $coaster,
        ]);

        if ($rating) {
            return $rating;
        }

        $rating = new UserCoasterRating($user, $coaster);
        $rating->setRating(self::DEFAULT_RATING);

        $this->entityManager->persist($rating);

        return $rating;
    }

    private function updateRatings(
        UserCoasterRating $winner,
        UserCoasterRating $loser
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

        if ($minimumGames < 10) {
            return 40;
        }

        if ($minimumGames < 30) {
            return 24;
        }

        return 16;
    }

    /**
     * @return array<UserCoasterRating>
     */
    public function calculateRankingByFilter(RankingFilter $filter): array
    {
        return $this->userCoasterRatingRepository->calculateRankingByFilter($filter);
    }
}
