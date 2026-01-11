<?php

namespace App\Controller;

use App\Repository\CoasterRepository;
use App\Service\UnitConversionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CoasterController extends AbstractController
{
    public bool $useMetricUnits = true;

    public function __construct(private readonly UnitConversionService $unitConversionService,
    )
    {
    }


    #[Route('/coaster/{id}', name: 'app_coaster_show')]
    public function show(int $id, CoasterRepository $coasterRepository): Response
    {
        $coaster = $coasterRepository->find(1);

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

        $coaster = [
            'track' => $track,
            'id' => $id,
            'name' => 'Blue Fire',
            'park' => 'Europa Park',
            'country' => 'Deutschland',
            'image' => $coaster->getCdnImageUrl() ?: $coaster->getRcdbImageUrl(),
            'ridden' => true,
            'manufacturer' => 'Mack Rides',
            'type' => 'Launch Coaster',
            'drive' => 'LSM Launch',
            'inversions' => 4,
            'length' => '1056 m',
            'height' => '38 m',
            'year' => 2009,
            // ✨ neu für Ratings:
            'avgRating' => 4.2,
            'userRating' => 3.8,
            'avgCategories' => [
                'Intensität' => 4,
                'Theming' => 5,
                'Spaß' => 4,
                'Gesamteindruck' => 3,
            ],
            'userCategories' => [
                'Intensität' => 1,
                'Theming' => 2,
                'Spaß' => 4,
                'Gesamteindruck' => 1,
            ]
        ];

        return $this->render('coaster/detail.html.twig', [
            'coaster' => $coaster,
        ]);
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
