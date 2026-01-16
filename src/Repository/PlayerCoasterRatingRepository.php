<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Coaster;
use App\Entity\Player;
use App\Entity\PlayerCoasterRating;
use App\Entity\UserCoasterRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerCoasterRating>
 */
class PlayerCoasterRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerCoasterRating::class);
    }

    public function findOrCreate(Player $player, Coaster $coaster): PlayerCoasterRating
    {
        $rating = $this->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if ($rating) {
            return $rating;
        }

        return new PlayerCoasterRating($player, $coaster);
    }
}
