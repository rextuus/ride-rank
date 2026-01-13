<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Repository\CoasterRepository;

readonly class ChallengeCoasterPoolProvider
{
    public function __construct(private CoasterRepository $coasterRepository)
    {
    }

    /**
     * @return array<Coaster>
     */
    public function getCoasterByRandomLocationType(Coaster $anchorCoaster): array
    {
        $location = null;
        $tries = 0;
        while ($location === null) {
            $locationType = LocationType::random();
            $location = $anchorCoaster->getFirstLocationOfType($locationType);
            $tries++;
            if ($tries > 10) {
                // TODO
            }
        }

        return $this->coasterRepository->findCoastersByLocation(
            $location,
            false,
            1000
        );
    }

    /**
     * @return array<Coaster>
     */
    public function getCoasterFromSameCountry(Coaster $anchorCoaster): array
    {
        $country = $anchorCoaster->getFirstLocationOfType(LocationType::COUNTRY);
        if ($country === null) {
            //TODO
        }

        return $this->coasterRepository->findCoastersByLocation(
            $country,
            false,
            1000
        );
    }

    /**
     * @return array<Coaster>
     */
    public function getCoasterByLowComparisonCount(): array
    {
        return $this->coasterRepository->findLowComparisonRateCoasters(1000);
    }

    /**
     * @return array<Coaster>
     */
    public function getCoasterBySimilarEloRating(Coaster $anchorCoaster): array
    {
        return $this->coasterRepository->findDistinctCoasterWithSimilarEloRating(
            $anchorCoaster->getRating(),
            1000
        );
    }
}
