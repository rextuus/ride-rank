<?php

namespace App\Twig\Components;

use App\Repository\CoasterRepository;
use App\Repository\RiddenCoasterRepository;
use App\Service\Player\PlayerContext;
use App\Service\Player\RiddenCoasterService;
use App\Service\Util\CoasterNormalizer;
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
        private readonly CoasterNormalizer $coasterNormalizer,
        private readonly PlayerContext $playerContext,
        private readonly RiddenCoasterService $riddenCoasterService,
    ) {
    }

    #[LiveAction]
    public function toggleSeen(#[LiveArg] int $id): void
    {
        // 1️⃣ Get the current player
        $player = $this->playerContext->getCurrentPlayer();

        // 2️⃣ Load the coaster
        $coaster = $this->coasterRepository->find($id);
        if (!$coaster) {
            return;
        }

        // 3️⃣ Delegate toggle to the service
        $this->riddenCoasterService->toggle($player, $coaster);

        // Refresh normalized data
        $this->coasterData = $this->coasterNormalizer->normalize($coaster);
    }
}
