<?php

namespace App\Twig\Components;

use App\Common\Entity\Enum\LocationType;
use App\Repository\CoasterRepository;
use App\Repository\RiddenCoasterRepository;
use App\Service\Player\PlayerContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Collection extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $columns = 2;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public function __construct(
        private readonly RiddenCoasterRepository $riddenRepo,
        private readonly CoasterRepository $coasterRepo,
        private readonly PlayerContext $playerContext
    ) {
    }

    public function getCoastersByPark(): array
    {
        $player = $this->playerContext->getCurrentPlayer();

        $riddenCoasters = $player->getRiddenCoasters();
        $riddenMap = [];
        $parkRiddenCount = [];

        foreach ($riddenCoasters as $ridden) {
            $coaster = $ridden->getCoaster();
            $riddenMap[$coaster->getId()] = true;

            $park = $coaster->getFirstLocationOfType(LocationType::AMUSEMENT_PARK);
            if ($park) {
                $parkName = $park->getName() ?? 'Unknown park';
                $parkRiddenCount[$parkName] = ($parkRiddenCount[$parkName] ?? 0) + 1;
            }
        }

        // Sort parks by ridden count descending
        arsort($parkRiddenCount);
        $sortedParkNames = array_keys($parkRiddenCount);

        $totalParks = count($sortedParkNames);
        if ($totalParks === 0) {
            return [];
        }

        // Pagination: one park per page
        $this->page = max(1, min($this->page, $totalParks));
        $currentParkName = $sortedParkNames[$this->page - 1];

        // Need to find by name because findByParks uses ident, but sortedParkNames are names

        $allParkCoasters = $this->coasterRepo->getAllCoasterOfPark($currentParkName);

        $maxElo = $this->coasterRepo->getMaxElo();
        $globalRanks = $this->coasterRepo->getGlobalRanks();

        $coasters = [];
        $riddenInThisPark = 0;
        foreach ($allParkCoasters as $coaster) {
            $elo = (int) round($coaster->getRating());
            $eloPercent = $maxElo > 0 ? (int) round(($elo / $maxElo) * 100) : 0;
            $isRidden = isset($riddenMap[$coaster->getId()]);
            if ($isRidden) {
                $riddenInThisPark++;
            }

            $location = $coaster->getFirstLocationOfType(LocationType::AMUSEMENT_PARK);

            $coasters[] = [
                'id' => $coaster->getId(),
                'name' => $coaster->getName() ?? 'Unknown',
                'park' => $currentParkName,
                'country' => $location?->getName(),
                'image' => $coaster->getCdnImageUrl() ?? $coaster->getRcdbImageUrl(),
                'ridden' => $isRidden,
                'elo' => $elo,
                'eloPercent' => $isRidden ? $eloPercent : 0,
                'rank' => $globalRanks[$coaster->getId()] ?? null,
            ];
        }

        return [
            $currentParkName => [
                'coasters' => $coasters,
                'stats' => [
                    'ridden' => $riddenInThisPark,
                    'total' => count($allParkCoasters),
                    'percent' => count($allParkCoasters) > 0 ? round(
                        ($riddenInThisPark / count($allParkCoasters)) * 100
                    ) : 0,
                ]
            ]
        ];
    }

    public function getTotalPages(): int
    {
        $player = $this->playerContext->getCurrentPlayer();

        $riddenCoasters = $player->getRiddenCoasters();
        $parks = [];
        foreach ($riddenCoasters as $ridden) {
            $park = $ridden->getCoaster()->getFirstLocationOfType(LocationType::AMUSEMENT_PARK);
            if ($park) {
                $parks[$park->getName()] = true;
            }
        }

        return count($parks);
    }
}
