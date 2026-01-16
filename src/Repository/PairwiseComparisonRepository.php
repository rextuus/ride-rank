<?php

namespace App\Repository;

use App\Entity\Coaster;
use App\Entity\PairwiseComparison;
use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<PairwiseComparison>
 */
class PairwiseComparisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PairwiseComparison::class);
    }

    /**
     * @return array<int>
     */
    public function findRecentCoasterIdsForPlayer(Player $player, int $limit): array
    {
        $qb = $this->createQueryBuilder('pc')
            ->select('IDENTITY(pc.coasterA) AS coasterA_id, IDENTITY(pc.coasterB) AS coasterB_id')
            ->where('pc.player = :player')
            ->setParameter('player', $player)
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults($limit);

        $rows = $qb->getQuery()->getArrayResult();

        $ids = [];

        foreach ($rows as $row) {
            if ($row['coasterA_id']) {
                $ids[] = (int) $row['coasterA_id'];
            }
            if ($row['coasterB_id']) {
                $ids[] = (int) $row['coasterB_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<PairwiseComparison>
     */
    public function findByPlayerAndCoaster(Player $player, Coaster $coaster): array
    {
        $qb = $this->createQueryBuilder('pc');
        $qb->select('pc')
            ->join(Coaster::class, 'c', 'WITH', 'pc.coasterA = c OR pc.coasterB = c')
            ->join(Player::class, 'p', 'WITH', 'pc.player = p')
            ->where($qb->expr()->eq('p.deviceHash', ':player'))
            ->setParameter('player', $player->getDeviceHash())
            ->andWhere($qb->expr()->orX(
                'pc.coasterA = :coaster',
                'pc.coasterB = :coaster'
            ))
            ->setParameter('coaster', $coaster)
            ->orderBy('pc.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<PairwiseComparison>
     */
    public function findNewestWithOptionalCoaster(
        ?Coaster $coaster,
        int $limit = 50
    ): array {
        $qb = $this->createQueryBuilder('pc')
            ->select('pc')
            ->join('pc.player', 'p')
            ->join('pc.coasterA', 'ca')
            ->join('pc.coasterB', 'cb')
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($coaster) {
            $qb->andWhere('pc.coasterA = :coaster OR pc.coasterB = :coaster')
                ->setParameter('coaster', $coaster);
        }

        return $qb->getQuery()->getResult();
    }

    public function getPrimaryStatsForCoaster(Coaster $coaster): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
SELECT
    COUNT(*) AS total_comparisons,
    COUNT(DISTINCT pc.player_id) AS unique_players,
    SUM(CASE WHEN pc.winner_id = :coasterId THEN 1 ELSE 0 END) * 1.0 / COUNT(*) AS win_rate,
    SUM(CASE WHEN pc.created_at >= DATETIME('now', '-1 day') THEN 1 ELSE 0 END) AS last_24h,
    SUM(CASE WHEN pc.created_at >= DATETIME('now', '-7 day') THEN 1 ELSE 0 END) AS last_7d
FROM pairwise_comparison pc
WHERE pc.coaster_a_id = :coasterId
   OR pc.coaster_b_id = :coasterId
SQL;

        return $conn->executeQuery($sql, [
            'coasterId' => $coaster->getId(),
        ])->fetchAssociative();
    }
}
