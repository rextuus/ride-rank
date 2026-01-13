<?php

namespace App\Service\Ranking;

use App\Entity\PairwiseComparison;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

readonly class PairwiseComparisonSubscriber implements EventSubscriber
{
    public function __construct(
        private UserCoasterRatingService $ratingService
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof PairwiseComparison) {
            return;
        }

        if ($entity->getUser() === null) {
            return;
        }

        $this->ratingService->applyComparison($entity);
    }
}
