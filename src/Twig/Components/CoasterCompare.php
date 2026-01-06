<?php

namespace App\Twig\Components;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Repository\CoasterRepository;
use App\Service\Rating\EloRatingService;
use App\Service\Rating\MatchupSelectionService;
use App\Service\Rating\PairwiseComparisonService;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;

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

    public function __construct(
        private readonly CoasterRepository $coasterRepository,
        private readonly MatchupSelectionService $matchupSelectionService,
        private readonly EloRatingService $eloRatingService,
        private readonly PairwiseComparisonService $pairwiseComparisonService,
        private readonly Security $security,
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
        $this->pickNewCoasters();
    }

    private function persistChoice(int $winnerId): void
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
        $winner = $this->coasterRepository->find($winnerId);

        if (!$coasterA || !$coasterB || !$winner) {
            return;
        }

        $comparison = $this->pairwiseComparisonService->recordComparison(
            $coasterA,
            $coasterB,
            $winner,
            $this->security->getUser(),
            $responseTimeMs
        );

        $this->eloRatingService->updateRatings($comparison);
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
        $left = $this->coasterRepository->find(203);
        $right = $this->coasterRepository->find(201);

        $coasters = $this->matchupSelectionService->getNextMatchup(null);
        $left = $coasters[0];
        $right = $coasters[1];

        $this->left = $this->normalizeCoaster($left);
        $this->right = $this->normalizeCoaster($right);
    }

// ... existing code ...

    private function normalizeCoaster(Coaster $coaster): array
    {
        $park = '';
        $country = '';

        foreach ($coaster->getLocations() as $location) {
            if ($location->getType() === LocationType::AMUSEMENT_PARK) {
                $park = $location->getName();
            }
            if ($location->getType() === LocationType::COUNTRY) {
                $country = $location->getName();
            }
        }

        $image = $coaster->getCdnImageUrl();
        if ($image === null) {
            $image = $coaster->getRcdbImageUrl();
        }

        $track = null;
        if ($coaster->getTrack()) {
            $trackEntity = $coaster->getTrack();
            $track = [
                'length' => $trackEntity->getLength(),
                'height' => $trackEntity->getHeight(),
                'speed' => $trackEntity->getSpeed(),
                'inversions' => $trackEntity->getInversions(),
                'duration' => $trackEntity->getDuration(),
                'drop' => $trackEntity->getDrop(),
                'verticalAngle' => $trackEntity->getVerticalAngle(),
            ];
        }

        return [
            'id' => $coaster->getId(),
            'name' => $coaster->getName(),
            'image' => $image,
            'park' => $park,
            'country' => $country,
            'track' => $track,
        ];
    }

// ... existing code ...
}
