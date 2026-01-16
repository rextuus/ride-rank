<?php

namespace App\Repository;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\Location;
use App\Entity\PairwiseComparison;
use App\Entity\Player;
use App\Entity\PlayerCoasterRating;
use App\Service\PlayerRating\EloCoasterDto;
use App\Service\Rating\ModelExclusionService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /* -----------------------------
     * PRIVATE HELPER
     * ----------------------------- */

    /**
     * Apply an optional filter to exclude coasters with certain models.
     *
     * @param QueryBuilder $qb
     * @param string[]|null $excludeModelIdents
     */
    private function applyModelExclusion(QueryBuilder $qb, ?array $excludeModelIdents): void
    {
        if (empty($excludeModelIdents)) {
            return;
        }

        $subQb = $this->getEntityManager()->createQueryBuilder()
            ->select('c_sub.id')
            ->from(Coaster::class, 'c_sub')
            ->innerJoin('c_sub.models', 'm_sub')
            ->where('m_sub.ident IN (:excludedModels)');

        $qb->andWhere(
            $qb->expr()->notIn('c.id', $subQb->getDQL())
        )->setParameter('excludedModels', $excludeModelIdents);
    }

    /* -----------------------------
     * GENERAL QUERIES
     * ----------------------------- */

    /**
     * @return array<Coaster>
     */
    public function findCoastersByLocation(Location $location, bool $sortByPopularity = true, int $limit = 100, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.locations', 'l')
            ->andWhere('l.type = :locationType')
            ->setParameter('locationType', $location->getType()->value)
            ->andWhere('l.ident = :countryName')
            ->setParameter('countryName', $location->getIdent())
            ->setMaxResults($limit);

        if ($sortByPopularity) {
            $qb->addSelect('(c.rating * 0.7 + c.comparisonsCount * 0.3) AS HIDDEN combinedScore')
                ->orderBy('combinedScore', 'DESC');
        }

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findTopRated(float $topPercent = 10.0, int $limit = 100, ?array $excludeModels = null): array
    {
        $totalCoasters = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalCoasters == 0) {
            return [];
        }

        $topCount = (int) ceil($totalCoasters * ($topPercent / 100));

        $qb = $this->createQueryBuilder('c')
            ->addSelect('(c.rating * 0.7 + c.comparisonsCount * 0.3) AS HIDDEN combinedScore')
            ->orderBy('combinedScore', 'DESC')
            ->setMaxResults(min($limit, $topCount));

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findDistinctCoasterWithSimilarEloRating(float $rating, int $limit = 100, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->addSelect('ABS(c.rating - :targetRating) AS HIDDEN ratingDiff')
            ->setParameter('targetRating', $rating)
            ->orderBy('ratingDiff', 'ASC')
            ->addOrderBy('c.comparisonsCount', 'ASC')
            ->setMaxResults($limit);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findLowComparisonRateCoasters(int $limit = 100, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.comparisonsCount < :threshold')
            ->setParameter('threshold', 100)
            ->setMaxResults($limit);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function getMaxElo(): float
    {
        return (float) $this->createQueryBuilder('c')
            ->select('MAX(c.rating)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCoasterWithHighestElo(?Player $player = null, int $limit = 20, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($player !== null) {
            $qb->leftJoin(
                PlayerCoasterRating::class,
                'pcr',
                'WITH',
                'pcr.coaster = c AND pcr.player = :player'
            )
                ->setParameter('player', $player)
                ->addSelect('pcr'); // important: select joined entity
        }

        $qb->orderBy('c.rating', 'DESC')
            ->setMaxResults($limit);

        // Apply optional model exclusion
        if (!empty($excludeModels)) {
            $subQb = $this->getEntityManager()->createQueryBuilder()
                ->select('c_sub.id')
                ->from(Coaster::class, 'c_sub')
                ->innerJoin('c_sub.models', 'm_sub')
                ->where('m_sub.ident IN (:excludedModels)');

            $qb->andWhere($qb->expr()->notIn('c.id', $subQb->getDQL()))
                ->setParameter('excludedModels', $excludeModels);
        }

        $results = $qb->getQuery()->getResult();

        $final = [];

        foreach ($results as $row) {
            // Handle both cases: simple Coaster object or [Coaster, PlayerCoasterRating|null]
            if ($row instanceof Coaster) {
                $coaster = $row;
                $playerRating = null;
            } elseif (is_array($row)) {
                $coaster = $row[0] ?? null;
                $playerRating = $row[1] ?? null;
            } else {
                continue;
            }

            if (!$coaster instanceof Coaster) {
                continue;
            }

            // fallback if join didn't return rating
            if ($player !== null && $playerRating === null) {
                $playerRating = $this->getEntityManager()
                    ->getRepository(PlayerCoasterRating::class)
                    ->findOneBy(['player' => $player, 'coaster' => $coaster]);
            }

            // global stats
            $wins = (int) $this->getEntityManager()
                ->createQueryBuilder()
                ->select('COUNT(pc.id)')
                ->from(PairwiseComparison::class, 'pc')
                ->where('pc.winner = :coaster')
                ->setParameter('coaster', $coaster)
                ->getQuery()
                ->getSingleScalarResult();

            $losses = (int) $this->getEntityManager()
                ->createQueryBuilder()
                ->select('COUNT(pc.id)')
                ->from(PairwiseComparison::class, 'pc')
                ->where('pc.loser = :coaster')
                ->setParameter('coaster', $coaster)
                ->getQuery()
                ->getSingleScalarResult();

            $personalWins = $playerRating?->getWins() ?? 0;
            $personalLosses = $playerRating?->getLosses() ?? 0;
            $personalRatingValue = $playerRating?->getRating();

            $final[] = new EloCoasterDto(
                coaster: $coaster,
                wins: $wins,
                losses: $losses,
                personalWins: $personalWins,
                personalLosses: $personalLosses,
                personalRating: $personalRatingValue
            );
        }

        return $final;
    }

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

    public function findRecentForPlayer(Player $player, int $limit): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->innerJoin(PairwiseComparison::class, 'pc', 'WITH', 'pc.coasterA = c OR pc.coasterB = c')
            ->innerJoin(Player::class, 'p', 'WITH', 'pc.player = p')
            ->andWhere($qb->expr()->eq('p.deviceHash', ':deviceHash'))
            ->setParameter('deviceHash', $player->getDeviceHash())
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findKnowledgeCandidates(array $excludedIds, int $limit, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.rating', 'DESC')
            ->addOrderBy('c.comparisonsCount', 'DESC')
            ->setMaxResults($limit);

        if (!empty($excludedIds)) {
            $qb->andWhere('c.id NOT IN (:excluded)')
                ->setParameter('excluded', $excludedIds);
        }

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function getAllCoasterOfPark(string $currentParkName, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.locations', 'l')
            ->where('l.type = :type')
            ->andWhere('l.ident = :parkName')
            ->setParameter('type', LocationType::AMUSEMENT_PARK->value)
            ->setParameter('parkName', $currentParkName);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findByParks(array $parkIds, int $limit = 100, ?array $excludeModels = null): array
    {
        if (empty($parkIds)) return [];

        $qb = $this->createQueryBuilder('c')
            ->distinct()
            ->innerJoin('c.locations', 'l')
            ->where('l.type = :type')
            ->andWhere('l.id IN (:parkIds)')
            ->setParameter('type', LocationType::AMUSEMENT_PARK->value)
            ->setParameter('parkIds', $parkIds)
            ->setMaxResults($limit);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findByCountries(array $countryIds, int $limit = 100, ?array $excludeModels = null): array
    {
        if (empty($countryIds)) return [];

        $qb = $this->createQueryBuilder('c')
            ->distinct()
            ->innerJoin('c.locations', 'l')
            ->where('l.type = :type')
            ->andWhere('l.id IN (:countryIds)')
            ->setParameter('type', LocationType::COUNTRY->value)
            ->setParameter('countryIds', $countryIds)
            ->setMaxResults($limit);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findTopRatedByCountry(?Location $country, int $limit = 40, ?array $excludeModels = null): array
    {
        if (!$country) return [];

        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.locations', 'l')
            ->andWhere('l.type = :type')
            ->andWhere('l.ident = :countryIdent')
            ->setParameter('type', LocationType::COUNTRY->value)
            ->setParameter('countryIdent', $country->getIdent())
            ->orderBy('c.rating', 'DESC')
            ->addOrderBy('c.selectionSeed', 'ASC')
            ->setMaxResults($limit);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findRandomGlobal(int $limit = 30, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('RAND()')
            ->setMaxResults($limit);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function findLowExposureGlobal(int $limit = 50, ?array $excludeModels = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.comparisonsCount', 'ASC')
            ->addOrderBy('c.rating', 'DESC')
            ->addOrderBy('c.selectionSeed', 'ASC')
            ->setMaxResults($limit);

        $this->applyModelExclusion($qb, $excludeModels);

        return $qb->getQuery()->getResult();
    }

    public function getGlobalRank(Coaster $coaster): int
    {
        $conn = $this->getEntityManager()->getConnection();

        return (int) $conn->fetchOne(
            'SELECT COUNT(*) + 1 FROM coaster WHERE rating > :rating',
            ['rating' => $coaster->getRating()]
        );
    }
}
