<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Coaster;
use App\Entity\Player;
use App\Entity\RiddenCoaster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RiddenCoaster>
 */
class RiddenCoasterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiddenCoaster::class);
    }

    public function existsForPlayerAndCoaster(Player $player, Coaster $coaster): bool
    {
        return $this->count([
            'player' => $player,
            'coaster' => $coaster,
        ]) > 0;
    }

    /**
     * @return array<int>
     */
    public function findRiddenCoasterIdsForPlayer(Player $player): array
    {
        return array_column(
            $this->createQueryBuilder('r')
                ->select('IDENTITY(r.coaster)')
                ->where('r.player = :player')
                ->setParameter('player', $player)
                ->getQuery()
                ->getScalarResult(),
            1
        );
    }

    /**
     * @return array<RiddenCoaster>
     */
    public function findRiddenCoastersForPlayer(Player $player): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->where('r.player = :player')
            ->setParameter('player', $player);

        return $qb->getQuery()->getResult();
    }

}
