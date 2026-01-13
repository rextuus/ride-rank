<?php

declare(strict_types=1);

namespace App\Service\Util;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\User;
use App\Repository\RiddenCoasterRepository;

readonly class CoasterNormalizer
{
    public function __construct(
        private UnitConversionService $unitConversionService,
        private RiddenCoasterRepository $riddenCoasterRepository,
    )
    {
    }

    public function normalize(Coaster $coaster, ?User $user = null, bool $useMetricUnits = true): array
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
            $findModel = '-';
            $modelToHide = ['Other', 'All Models', 'New', 'Custom'];

            foreach ($coaster->getModels() as $model) {
                if ($findModel !== '-'){
                    break;
                }
                if (!in_array($model->getIdent(), $modelToHide)) {
                    $findModel = $model->getName();
                }
            }

            $trackEntity = $coaster->getTrack();
            $track = [
                'length' => [
                    'value' => $this->convertLength($trackEntity->getLength()),
                    'favorite' => false,
                    'unit' => $useMetricUnits ? 'm' : 'ft'
                ],
                'height' => [
                    'value' => $this->convertLength($trackEntity->getHeight()),
                    'favorite' => false,
                    'unit' => $useMetricUnits ? 'm' : 'ft'
                ],
                'speed' => [
                    'value' => $this->convertSpeed($trackEntity->getSpeed()),
                    'favorite' => false,
                    'unit' => $useMetricUnits ? 'km/h' : 'mph'
                ],
                'inversions' => ['value' => $trackEntity->getInversions() ?: 0, 'favorite' => false],
                'duration' => ['value' => $trackEntity->getDuration(), 'favorite' => false, 'unit' => 's'],
                'drop' => [
                    'value' => $this->convertLength($trackEntity->getDrop()),
                    'favorite' => false,
                    'unit' => $useMetricUnits ? 'm' : 'ft'
                ],
                'verticalAngle' => ['value' => $trackEntity->getVerticalAngle(), 'favorite' => false, 'unit' => '°'],
                'manufacturer' => ['value' => $coaster->getManufacturer()->getName(), 'favorite' => false],
                'model' => ['value' => $findModel, 'favorite' => false],
            ];
        }

        $isSeen = false;
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

    private function convertLength(?float $feet, bool $useMetricUnits = true): ?float
    {
        if ($feet === null) {
            return null;
        }
        return $useMetricUnits ? $this->unitConversionService->feetToMeters($feet) : $feet;
    }

    private function convertSpeed(?float $mph, bool $useMetricUnits = true): ?float
    {
        if ($mph === null) {
            return null;
        }

        return $useMetricUnits ? $this->unitConversionService->mphToKmh($mph) : $mph;
    }
}
