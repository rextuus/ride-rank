<?php

namespace App\Twig\Components;

use App\Entity\Coaster;
use App\Entity\RiddenCoaster;
use App\Entity\User;
use App\Repository\CoasterRepository;
use App\Repository\RiddenCoasterRepository;
use App\Service\Util\CoasterNormalizer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CoasterDetail
{
    use DefaultActionTrait;

    #[LiveProp]
    public array $coasterData = [];

    public function __construct(
        private readonly CoasterRepository $coasterRepository,
        private readonly RiddenCoasterRepository $riddenCoasterRepository,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly CoasterNormalizer $coasterNormalizer
    ) {
    }

    #[LiveAction]
    public function toggleSeen(#[LiveArg] int $id): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $coaster = $this->coasterRepository->find($id);
        if (!$coaster) {
            return;
        }

        $riddenCoaster = $this->riddenCoasterRepository->findOneBy([
            'user' => $user,
            'coaster' => $coaster,
        ]);

        if ($riddenCoaster) {
            $this->entityManager->remove($riddenCoaster);
        } else {
            $riddenCoaster = new RiddenCoaster();
            $riddenCoaster->setUser($user);
            $riddenCoaster->setCoaster($coaster);
            $riddenCoaster->setRiddenAt(new DateTimeImmutable());
            $this->entityManager->persist($riddenCoaster);
        }

        $this->entityManager->flush();

        // Refresh normalized data
        $this->coasterData = $this->coasterNormalizer->normalize($coaster, $user);
    }
}
