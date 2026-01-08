<?php

declare(strict_types=1);

namespace App\Repository;

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
}
