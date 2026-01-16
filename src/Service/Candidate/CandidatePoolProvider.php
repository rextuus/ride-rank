<?php

declare(strict_types=1);

namespace App\Service\Candidate;

use App\Entity\Player;
use App\Repository\CoasterRepository;
use App\Service\Player\PlayerExperienceLevel;
use App\Service\Rating\ModelExclusionService;

readonly class CandidatePoolProvider
{
    public function __construct(
        private CoasterRepository $coasterRepository,
        private ModelExclusionService $modelExclusionService
    )
    {
    }

    public function buildCandidatePools(Player $player, array $context, array $excludedIds): array
    {
        $exclusions = $this->modelExclusionService->getExcludedModelsByPlayer($player);
        return match ($player->getExperienceLevel()) {
            PlayerExperienceLevel::NEWBIE => [
                [$this->coasterRepository->findTopRatedByCountry($player->getHomeCountry(), 40, $exclusions), 0.6],
                [$this->coasterRepository->findByCountries($context['countries'], 30, $exclusions), 0.3],
                [$this->coasterRepository->findKnowledgeCandidates($excludedIds, 20, $exclusions), 0.1],
            ],

            PlayerExperienceLevel::LOCAL => [
                [$this->coasterRepository->findByParks($context['parks'], 50, $exclusions), 0.4],
                [$this->coasterRepository->findByCountries($context['countries'], 40, $exclusions), 0.4],
                [$this->coasterRepository->findKnowledgeCandidates($excludedIds, 30, $exclusions), 0.2],
            ],

            PlayerExperienceLevel::ENTHUSIAST => [
                [$this->coasterRepository->findByParks($context['parks'], 40, $exclusions), 0.3],
                [$this->coasterRepository->findKnowledgeCandidates($excludedIds, 60, $exclusions), 0.5],
                [$this->coasterRepository->findRandomGlobal(30), 0.2],
            ],

            PlayerExperienceLevel::EXPERT => [
                [$this->coasterRepository->findKnowledgeCandidates($excludedIds, 80, $exclusions), 0.6],
                [$this->coasterRepository->findLowExposureGlobal(50, $exclusions), 0.4],
            ],
        };
    }
}
