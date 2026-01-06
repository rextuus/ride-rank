<?php

namespace App\Repository;

use App\Common\Entity\Enum\LocationType;
use App\Entity\User;
use App\Entity\UserCoasterAffinity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCoasterAffinity>
 */
class UserCoasterAffinityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCoasterAffinity::class);
    }

    //    /**
    //     * @return UserCoasterAffinity[] Returns an array of UserCoasterAffinity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?UserCoasterAffinity
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return array<UserCoasterAffinity>
     */
    public function findHighestConfidenceCoastersForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('uca');
        $qb->where('uca.user = :user')
            ->setParameter('user', $user);
        $qb->orderBy('uca.confidence', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
