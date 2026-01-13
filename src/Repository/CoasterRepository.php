<?php

namespace App\Repository;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\Location;
use App\Entity\User;
use App\Service\Ranking\EloCoasterDto;
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

    public function getMaxElo(): float
    {
        return (float) $this->createQueryBuilder('c')
            ->select('MAX(c.rating)')
            ->getQuery()
            ->getSingleScalarResult();
    }


    /**
     * @return array<EloCoasterDto>
     */
    public function getCoasterWithHighestElo(?User $user = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('c');

        // Join UserCoasterRating if user is provided
        if ($user !== null) {
            $qb->leftJoin(
                'App\Entity\UserCoasterRating',
                'ucr',
                'WITH',
                'ucr.coaster = c AND ucr.user = :user'
            )->setParameter('user', $user);
        }

        $qb->orderBy('c.rating', 'DESC')
            ->setMaxResults($limit);

        $coasters = $qb->getQuery()->getResult();

        $result = [];
        foreach ($coasters as $coaster) {
            $wins = 0;
            $losses = 0;
            $personalWins = 0;
            $personalLosses = 0;

            if ($user !== null) {
                // Get global stats from comparisons
                $comparisons = $this->getEntityManager()
                    ->createQueryBuilder()
                    ->from('App\Entity\PairwiseComparison', 'pc')
                    ->select('COUNT(pc.id) as count')
                    ->where('pc.winner = :coaster')
                    ->setParameter('coaster', $coaster)
                    ->getQuery()
                    ->getSingleScalarResult();
                $wins = (int)$comparisons;

                $comparisons = $this->getEntityManager()
                    ->createQueryBuilder()
                    ->from('App\Entity\PairwiseComparison', 'pc')
                    ->select('COUNT(pc.id) as count')
                    ->where('pc.loser = :coaster')
                    ->setParameter('coaster', $coaster)
                    ->getQuery()
                    ->getSingleScalarResult();
                $losses = (int)$comparisons;

                // Get personal stats from UserCoasterRating
                $userRating = $this->getEntityManager()
                    ->getRepository('App\Entity\UserCoasterRating')
                    ->findOneBy(['user' => $user, 'coaster' => $coaster]);

                if ($userRating !== null) {
                    $personalWins = $userRating->getWins();
                    $personalLosses = $userRating->getLosses();
                }
            }

            $result[] = new EloCoasterDto(
                coaster: $coaster,
                wins: $wins,
                losses: $losses,
                personalWins: $personalWins,
                personalLosses: $personalLosses,
            );
        }

        return $result;
    }

    /**
     * @return array<int, int> coasterId => rank
     */
    public function getGlobalRanks(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.id, c.rating')
            ->orderBy('c.rating', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $rank = 1;
        $ranks = [];

        foreach ($rows as $row) {
            $ranks[$row['id']] = $rank++;
        }

        return $ranks;
    }

    /**
     * @return array<Coaster>
     */
    public function findByParks(array $parkNames): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.locations', 'l')
            ->where('l.type = :type')
            ->andWhere('l.ident IN (:parks)')
            ->setParameter('type', LocationType::AMUSEMENT_PARK->value)
            ->setParameter('parks', $parkNames)
            ->getQuery()
            ->getResult();
    }

}
