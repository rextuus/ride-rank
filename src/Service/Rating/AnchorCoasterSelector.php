<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Common\Entity\Enum\LocationType;
use App\Entity\Coaster;
use App\Entity\Player;
use App\Repository\CoasterRepository;
use App\Repository\PlayerCoasterAffinityRepository;
use App\Service\Player\PlayerExperienceLevel;
use LogicException;

final readonly class AnchorCoasterSelector
{
    public function __construct(
        private CoasterRepository $coasterRepository,
        private PlayerCoasterAffinityRepository $affinityRepository,
        private SampleScoreCalculator $sampleScoreCalculator,
        private ModelExclusionService $modelExclusionService
    ) {
    }

    public function selectAnchorCoaster(Player $player): Coaster
    {
        $experience = $player->getExperienceLevel();

        $candidates = match ($experience) {
            PlayerExperienceLevel::NEWBIE =>
            $this->anchorsFromHomeCountry($player) ?: $this->anchorsFromGlobalFallback($player),

            PlayerExperienceLevel::LOCAL =>
            $this->anchorsFromFamiliarCountries($player) ?: $this->anchorsFromHomeCountry($player),

            PlayerExperienceLevel::ENTHUSIAST =>
            $this->anchorsFromExperiencedCountries($player) ?: $this->anchorsFromFamiliarCountries($player),

            PlayerExperienceLevel::EXPERT => $this->anchorsFromGlobalFallback($player),

            default => $this->anchorsFromHomeCountry($player),
        };

        if ($candidates === []) {
            throw new LogicException('No anchor candidates found');
        }

        shuffle($candidates);
        shuffle($candidates);

        $scored = [];
        foreach ($candidates as $coaster) {
            $scored[] = [
                'coaster' => $coaster,
                'score' => $this->sampleScoreCalculator->calculateAnchorScore($coaster, $player),
            ];
        }

        // Anchors should be stable → low temperature
        return $this->sampleScoreCalculator->sampleByScore($scored, 0.7);
    }

    /* -----------------------------
     * Candidate sources
     * ----------------------------- */

    private function anchorsFromHomeCountry(Player $player): array
    {
        $country = $player->getHomeCountry();
        if ($country === null) {
            return [];
        }

        $coasters = $this->coasterRepository->findTopRatedByCountry(
            $country,
            100,
            $this->modelExclusionService->getExcludedModelsByPlayer($player)
        );

        // Shuffle to prevent always returning the same starting coasters
        shuffle($coasters);

        return $coasters;
    }


    private function anchorsFromFamiliarCountries(Player $player): array
    {
        $countryIds = [];

        foreach ($player->getRiddenCoasters() as $ridden) {
            $country = $ridden->getCoaster()->getFirstLocationOfType(LocationType::COUNTRY);
            if ($country) {
                $countryIds[$country->getId()] = true;
            }
        }

        if ($countryIds === []) {
            return [];
        }

        return $this->coasterRepository->findByCountries(
            array_keys($countryIds),
            40,
            $this->modelExclusionService->getExcludedModelsByPlayer($player)
        );
    }

    private function anchorsFromExperiencedCountries(Player $player): array
    {
        $countryConfidence = [];

        foreach ($this->affinityRepository->findBy(['player' => $player]) as $affinity) {
            $coaster = $affinity->getCoaster();
            $country = $coaster->getFirstLocationOfType(LocationType::COUNTRY);

            if (!$country) {
                continue;
            }

            $countryConfidence[$country->getId()] =
                ($countryConfidence[$country->getId()] ?? 0)
                + abs($affinity->getConfidenceScore());
        }

        if ($countryConfidence === []) {
            return [];
        }

        arsort($countryConfidence);

        return $this->coasterRepository->findByCountries(
            array_keys($countryConfidence),
            50,
            $this->modelExclusionService->getExcludedModelsByPlayer($player)
        );
    }

    private function anchorsFromGlobalFallback(Player $player): array
    {
        return $this->coasterRepository->findTopRated(
            60,
            100,
            $this->modelExclusionService->getExcludedModelsByPlayer($player)
        );
    }
}
