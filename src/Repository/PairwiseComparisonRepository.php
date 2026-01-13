<?php

namespace App\Repository;

use App\Entity\Coaster;
use App\Entity\PairwiseComparison;
use App\Entity\User;
use App\Service\Ranking\RankingFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PairwiseComparison>
 */
class PairwiseComparisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PairwiseComparison::class);
    }

    //    /**
    //     * @return PairwiseComparison[] Returns an array of PairwiseComparison objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PairwiseComparison
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Coaster>
     */
    public function findRecentCoastersForUser(User $user, int $limit): array
    {
        $qb = $this->createQueryBuilder('pc')
            ->select('DISTINCT c')
            ->join('pc.coasterA', 'c')
            ->where('pc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults($limit);

        return array_map(
            fn ($row) => $row[0],
            $qb->getQuery()->getResult()
        );
    }
}
