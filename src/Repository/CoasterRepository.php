<?php

namespace App\Repository;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\Location;
use App\Entity\UserCoasterAffinity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coaster>
 */
class CoasterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coaster::class);
    }

    /**
     * @return array<Coaster>
     */
    public function findCoastersByLocation(Location $location, bool $sortByPopularity = true, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->innerJoin('c.locations', 'l')
            ->andWhere('l.type = :locationType')
            ->setParameter('locationType', $location->getType()->value)
            ->andWhere('l.ident = :countryName')
            ->setParameter('countryName', $location->getIdent())
            ->setMaxResults($limit);

        if ($sortByPopularity) {
            $qb->addSelect('(c.rating * 0.7 + c.comparisonsCount * 0.3) AS HIDDEN combinedScore')
                ->orderBy('combinedScore', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<Coaster>
     */
    public function findTopRated(float $topPercent = 10.0, int $limit = 100): array
    {
        // Step 1: Count total coasters
        $totalCoasters = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalCoasters == 0) {
            return [];
        }

        // Step 2: Compute number of coasters in top X%
        $topCount = (int) ceil($totalCoasters * ($topPercent / 100));

        // Step 3: Fetch top rated coasters
        $qb = $this->createQueryBuilder('c')
            ->addSelect('(c.rating * 0.7 + c.comparisonsCount * 0.3) AS HIDDEN combinedScore')
            ->orderBy('combinedScore', 'DESC');

        $qb->setMaxResults($topCount);
        if ($limit !== null && $limit < $topCount) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<Coaster>
     */
    public function findDistinctCoasterWithSimilarEloRating(float $rating, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('c');

        // We calculate the absolute difference from the target rating
        // and use it to find the closest matches.
        $qb->addSelect('ABS(c.rating - :targetRating) AS HIDDEN ratingDiff')
            ->setParameter('targetRating', $rating)
            // Prioritize coasters with fewer comparisons to help converge their true rating
            ->orderBy('ratingDiff', 'ASC')
            ->addOrderBy('c.comparisonsCount', 'ASC')
            ->setMaxResults(20);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<Coaster>
     */
    public function findLowComparisonRateCoasters(int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere('c.comparisonsCount < :threshold')
            ->setParameter('threshold', 100)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
