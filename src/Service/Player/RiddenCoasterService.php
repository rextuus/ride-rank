<?php

declare(strict_types=1);

namespace App\Service\Player;

use App\Entity\Coaster;
use App\Entity\Player;
use App\Entity\RiddenCoaster;
use App\Repository\RiddenCoasterRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RiddenCoasterService
{
    public function __construct(
        private RiddenCoasterRepository $riddenCoasterRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function toggle(Player $player, Coaster $coaster): void
    {
        $existing = $this->riddenCoasterRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if ($existing) {
            // Currently marked → unmark it
            $this->unmarkRidden($player, $coaster);
        } else {
            // Currently not marked → mark it
            $this->markRidden($player, $coaster);
        }
    }

    public function markRidden(Player $player, Coaster $coaster): void
    {
        $existing = $this->riddenCoasterRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if ($existing) {
            // Already marked ridden → nothing to do
            return;
        }

        $ridden = new RiddenCoaster();
        $ridden->setPlayer($player);
        $ridden->setCoaster($coaster);
        $ridden->setRidden(true);
        $player->addRiddenCoaster($ridden);

        $this->entityManager->persist($ridden);

        // Increase experience
        $player->gainExperience(1);

        $this->entityManager->persist($player);
        $this->entityManager->flush();
    }

    public function unmarkRidden(Player $player, Coaster $coaster): void
    {
        $existing = $this->riddenCoasterRepository->findOneBy([
            'player' => $player,
            'coaster' => $coaster,
        ]);

        if (!$existing) {
            // Nothing to undo
            return;
        }

        $player->reduceExperience(1);

        // Remove the record, but do NOT decrease experience
        $this->entityManager->remove($existing);
        $this->entityManager->flush();
    }
}
