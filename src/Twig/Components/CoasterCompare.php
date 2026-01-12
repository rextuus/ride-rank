<?php

namespace App\Twig\Components;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\RiddenCoaster;
use App\Entity\User;
use App\Repository\CoasterRepository;
use App\Repository\RiddenCoasterRepository;
use App\Service\Rating\EloRatingService;
use App\Service\Rating\MatchupSelectionService;
use App\Service\Rating\PairwiseComparisonService;
use App\Service\UnitConversionService;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;

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
        private readonly RiddenCoasterRepository $riddenCoasterRepository,
        private readonly MatchupSelectionService $matchupSelectionService,
        private readonly EloRatingService $eloRatingService,
        private readonly PairwiseComparisonService $pairwiseComparisonService,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly UnitConversionService $unitConversionService,
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
        if (isset($this->left['id']) && $this->left['id'] === $id) {
            $this->left = $this->normalizeCoaster($coaster);
        } elseif (isset($this->right['id']) && $this->right['id'] === $id) {
            $this->right = $this->normalizeCoaster($coaster);
        }
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
        $left = $this->coasterRepository->find(3);
        $right = $this->coasterRepository->find(2);

        $coasters = $this->matchupSelectionService->getNextMatchup(null);
        $left = $coasters[0];
        $right = $coasters[1];

        $this->left = $this->normalizeCoaster($left);
        $this->right = $this->normalizeCoaster($right);
        $this->markFavorites();
    }

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
                'length' => [
                    'value' => $this->convertLength($trackEntity->getLength()),
                    'favorite' => false,
                    'unit' => $this->useMetricUnits ? 'm' : 'ft'
                ],
                'height' => [
                    'value' => $this->convertLength($trackEntity->getHeight()),
                    'favorite' => false,
                    'unit' => $this->useMetricUnits ? 'm' : 'ft'
                ],
                'speed' => [
                    'value' => $this->convertSpeed($trackEntity->getSpeed()),
                    'favorite' => false,
                    'unit' => $this->useMetricUnits ? 'km/h' : 'mph'
                ],
                'inversions' => ['value' => $trackEntity->getInversions() ?: 0, 'favorite' => false],
                'duration' => ['value' => $trackEntity->getDuration(), 'favorite' => false, 'unit' => 's'],
                'drop' => [
                    'value' => $this->convertLength($trackEntity->getDrop()),
                    'favorite' => false,
                    'unit' => $this->useMetricUnits ? 'm' : 'ft'
                ],
                'verticalAngle' => ['value' => $trackEntity->getVerticalAngle(), 'favorite' => false, 'unit' => '°'],
                'manufacturer' => ['value' => $coaster->getManufacturer()->getName(), 'favorite' => false],
                'model' => ['value' => $coaster->getModels()->first() ? $coaster->getModels()->first()->getName() : null, 'favorite' => false],
            ];
        }

        $isSeen = false;
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $isSeen = $this->riddenCoasterRepository->findOneBy([
                'user' => $user,
                'coaster' => $coaster,
            ]) !== null;
        }

        return [
            'id' => $coaster->getId(),
            'name' => $coaster->getName(),
            'image' => $image,
            'park' => $park,
            'country' => $country,
            'track' => $track,
            'isSeen' => $isSeen,
        ];
    }

    private function markFavorites()
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

    private function convertLength(?float $feet): ?float
    {
        if ($feet === null) {
            return null;
        }
        return $this->useMetricUnits ? $this->unitConversionService->feetToMeters($feet) : $feet;
    }

    private function convertSpeed(?float $mph): ?float
    {
        if ($mph === null) {
            return null;
        }
        return $this->useMetricUnits ? $this->unitConversionService->mphToKmh($mph) : $mph;
    }
}
