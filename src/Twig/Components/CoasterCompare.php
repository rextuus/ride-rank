<?php

namespace App\Twig\Components;

use App\Entity\Coaster;
use App\Repository\CoasterRepository;
use App\Service\Player\PlayerContext;
use App\Service\Player\RiddenCoasterService;
use App\Service\PlayerRating\PairwiseComparisonService;
use App\Service\PlayerRating\PlayerCoasterRatingService;
use App\Service\Rating\EloRatingService;
use App\Service\Rating\MatchupSelectionService;
use App\Service\Util\CoasterNormalizer;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class CoasterCompare
{
    use DefaultActionTrait;

    #[LiveProp]
    public array $left = [];

    #[LiveProp]
    public array $right = [];

    #[LiveProp]
    public ?DateTime $lastInteraction = null;

    #[LiveProp]
    public bool $useMetricUnits = true;

    public function __construct(
        private readonly CoasterRepository $coasterRepository,
        private readonly MatchupSelectionService $matchupSelectionService,
        private readonly EloRatingService $eloRatingService,
        private readonly PlayerCoasterRatingService $playerCoasterRatingService,
        private readonly PairwiseComparisonService $pairwiseComparisonService,
        private readonly Security $security,
        private readonly CoasterNormalizer $coasterNormalizer,
        private readonly PlayerContext $playerContext,
        private readonly RiddenCoasterService $riddenCoasterService,
    ) {
        $this->lastInteraction = new DateTime();
    }

    public function mount(): void
    {
        $this->pickNewCoasters();
        $this->lastInteraction = new DateTime();
    }

    #[LiveAction]
    public function chooseLeft(): void
    {
        $this->persistChoice($this->left['id']);
        $this->pickNewCoasters();
    }

    #[LiveAction]
    public function chooseRight(): void
    {
        $this->persistChoice($this->right['id']);
        $this->pickNewCoasters();
    }

    #[LiveAction]
    public function skip(): void
    {
        $this->persistChoice($this->right['id'], true);

        $this->pickNewCoasters();
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

        // 4️⃣ Refresh normalized data
        if (isset($this->left['id']) && $this->left['id'] === $id) {
            $this->left = $this->normalizeCoaster($coaster);
        } elseif (isset($this->right['id']) && $this->right['id'] === $id) {
            $this->right = $this->normalizeCoaster($coaster);
        }
    }

    private function persistChoice(int $winnerId, bool $skip = false): void
    {
        $startMs = (float) $this->lastInteraction->format('Uv');
        $now = new DateTime();
        $endMs = (float) $now->format('Uv');
        $responseTimeMs = (int) ($endMs - $startMs);

        if (!isset($this->left['id']) || !isset($this->right['id'])) {
            return;
        }

        $coasterA = $this->coasterRepository->find($this->left['id']);
        $coasterB = $this->coasterRepository->find($this->right['id']);

        $winner = null;
        if (!$skip) {
            $winner = $this->coasterRepository->find($winnerId);
        }

        if (!$coasterA || !$coasterB || !$winner) {
            return;
        }

        $player = $this->playerContext->getCurrentPlayer();
        $comparison = $this->pairwiseComparisonService->recordComparison(
            $coasterA,
            $coasterB,
            $winner,
            $player,
            $responseTimeMs
        );

        $this->eloRatingService->applyComparison($comparison);
        $this->playerCoasterRatingService->applyComparison($comparison);
    }

    private function pickNewCoasters(): void
    {
        $this->lastInteraction = new DateTime();

        if ($this->coasterRepository->count([]) >= 2) {
            $this->pickNewRealCoasters();
            return;
        }

        $coasters = [
            [
                'name' => 'Wodan Timburcoaster',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Wodan_c6utyt.png'
            ],
            [
                'name' => 'Voltron Nevera',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Voltron_kasjwc.png'
            ],
            [
                'name' => 'Blue Fire',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766314/CoasterMatch/Europa%20Park/Europa_Park_Blue_Fire_w9rn63.png'
            ],
            [
                'name' => 'Schweizer Bobbahn',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766315/CoasterMatch/Europa%20Park/Europa_Park_Schweizer_Bobbahn_rpqoxh.png'
            ],
        ];

        shuffle($coasters);
        $this->left = $coasters[0];
        $this->right = $coasters[1];
    }

    #[LiveAction]
    public function pickNewRealCoasters(): void
    {
        $coasters = $this->coasterRepository->findAll();
        shuffle($coasters);

        // temp
        $left = $this->coasterRepository->find(3);
        $right = $this->coasterRepository->find(2);

        $player = $this->playerContext->getCurrentPlayer();
        $coasters = $this->matchupSelectionService->getNextMatchup($player);
        $left = $coasters[0];
        $right = $coasters[1];

        $this->left = $this->normalizeCoaster($left);
        $this->right = $this->normalizeCoaster($right);
        $this->markFavorites();
    }

    private function normalizeCoaster(Coaster $coaster): array
    {
        $player = $this->playerContext->getCurrentPlayer();

        return $this->coasterNormalizer->normalize($coaster, $this->useMetricUnits);
    }

    private function markFavorites(): void
    {
        // compare values of left and right track elements and set favorite flag
        foreach ($this->left['track'] as $key => $leftValue) {
            $rightValue = $this->right['track'][$key];
            // both are null => no winner
            if ($leftValue['value'] === null && $rightValue['value'] === null) {
                continue;
            }

            // only one value is null => winner is other one
            if ($leftValue['value'] === null && $rightValue['value'] !== null) {
                $this->right['track'][$key]['favorite'] = true;
                continue;
            }

            if ($leftValue['value'] !== null && $rightValue['value'] === null) {
                $this->left['track'][$key]['favorite'] = true;
                continue;
            }

            // bot values
            if ($leftValue['value'] > $rightValue['value']) {
                $this->left['track'][$key]['favorite'] = true;
            }
            if ($leftValue['value'] < $rightValue['value']) {
                $this->right['track'][$key]['favorite'] = true;
            }
        }
    }
}
