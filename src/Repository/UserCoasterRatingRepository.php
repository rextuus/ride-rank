<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserCoasterRating;
use App\Service\Ranking\RankingFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCoasterRating>
 */
class UserCoasterRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCoasterRating::class);
    }

    /**
     * @return array<UserCoasterRating>
     */
    public function calculateRankingByFilter(RankingFilter $filter): array
    {
        $qb = $this->createQueryBuilder('ucr');

        $qb->where('ucr.user = :user')
            ->setParameter('user', $filter->user);

        // Optional: ignore coasters with too few duels
        if ($filter->minimumGames > 1) {
            $qb->andWhere('ucr.gamesPlayed >= :minimumGames')
                ->setParameter('minimumGames', $filter->minimumGames);
        }

        $qb->orderBy('ucr.rating', 'DESC')          // highest Elo first
        ->addOrderBy('ucr.gamesPlayed', 'DESC') // tie-breaker: more games
        ->setMaxResults($filter->limit)
            ->setFirstResult($filter->offset);

        return $qb->getQuery()->getResult();
    }
}
