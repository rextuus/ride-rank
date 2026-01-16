<?php

declare(strict_types=1);

namespace App\Service\PlayerRating;

use App\Entity\Coaster;
use App\Entity\PairwiseComparison;
use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;

readonly class PairwiseComparisonService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function recordComparison(
        Coaster $coasterA,
        Coaster $coasterB,
        ?Coaster $winner,
        Player $player,
        ?int $responseTimeMs = null
    ): PairwiseComparison {
        $loser = null;
        if ($winner !== null) {
            $loser = ($winner->getId() === $coasterA->getId()) ? $coasterB : $coasterA;
        }

        $comparison = new PairwiseComparison();
        $comparison->setCoasterA($coasterA);
        $comparison->setCoasterB($coasterB);
        $comparison->setWinner($winner);
        $comparison->setLoser($loser);
        $comparison->setPlayer($player);
        $comparison->setResponseTimeMs($responseTimeMs);

        $this->entityManager->persist($comparison);
        $this->entityManager->flush();

        return $comparison;
    }
}
