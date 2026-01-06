<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Entity\Coaster;
use App\Entity\Location;
use App\Entity\User;
use App\Repository\CoasterRepository;
use App\Repository\LocationRepository;
use App\Repository\UserCoasterAffinityRepository;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * Responsible for selecting the next pair of coasters
 * to be shown to the user for comparison.
 *
 * This service MUST:
 * - Always return exactly two distinct coasters
 * - Prefer familiarity over randomness
 * - Balance exploration and exploitation
 * - Never persist anything
 */
readonly class MatchupSelectionService
{
    public function __construct(
        private UserCoasterAffinityRepository $coasterAffinityRepository,
        private CoasterRepository $coasterRepository,
        private LocationRepository $locationRepository,
        private ChallengeScoreCalculator $challengeScoreCalculator,
        private ChallengeCoasterPoolProvider $challengeCoasterPoolProvider,
        private LoggerInterface $matchupLogger
    ) {
    }

    public function determineLocalizationBaseOndRequest(): Location
    {
        // big countries get their own => default will be:
        // Europe = Germany
        // Asia = China
        // Rest of world = United States
        return $this->locationRepository->findOneBy(['ident' => 'United States']);
    }


    public function getNextMatchup(?User $user): array
    {
        $this->matchupLogger->info('Starting matchup selection', [
            'user' => $user?->getUserIdentifier(),
        ]);

        // TODO 1:
        // Determine user state:
        // - anonymous
        // - new (few or no comparisons)
        // - established (has affinity data)
        //
        // This will influence all later choices.
        $userState = $this->determineUserState($user);
        $this->matchupLogger->info('Determined user state', ['state' => $userState->value]);

        // TODO 2:
        // Select an "anchor" coaster:
        // Priority order:
        // - High-confidence UserCoasterAffinity coaster (if user exists)
        // - Popular coaster in user's country (if known)
        // - Globally high-rated coaster (top X% Elo)
        // - Random popular fallback
        //
        // Anchor must feel familiar or defensible.
        $anchorCoaster = $this->determineAnchorCoaster($userState, $user);
//         $anchorCoaster = $this->coasterRepository->find(204);

        $this->matchupLogger->info('Determined anchor coaster', [
            'id' => $anchorCoaster->getId(),
            'name' => $anchorCoaster->getName(),
        ]);

        // TODO 3:
        // Select a "challenger" coaster:
        // Bias toward:
        // - Similar Elo rating to anchor
        // - Low comparisonsCount (high uncertainty)
        // - Same country or nearby
        // - Shared attributes (manufacturer, type, park)
        //
        // Add controlled randomness (exploration).

        // low temperature = weights get closer to score. Higher temperature = more randomness.
        $temperature = match ($userState) {
            UserMatchupState::NEW_USER => 1.4,
            UserMatchupState::ESTABLISHED => 1.0,
            default => 0.2,
        };

        $challengerCoaster = $this->determineChallengerCoaster($anchorCoaster, $temperature, $user);

        if ($challengerCoaster === null) {
             $this->matchupLogger->error('Failed to find a challenger coaster');
             throw new LogicException('Matchup selection not implemented yet.');
        }

        $this->matchupLogger->info('Determined challenger coaster', [
            'id' => $challengerCoaster->getId(),
            'name' => $challengerCoaster->getName(),
            'temperature' => $temperature,
        ]);

        // TODO 4:
        // Ensure anchor and challenger are not the same coaster.
        // Ensure this exact pair was not shown very recently
        // (session-based or user-based memory).

        // TODO 5:
        // Fallback strategy:
        // If any step fails, gracefully degrade to
        // two popular but distinct coasters.

        // TODO 6:
        // Return exactly two Coaster entities in a deterministic order:
        // - anchor first
        // - challenger second

        return [$anchorCoaster, $challengerCoaster];
    }

    private function determineUserState(?User $user): UserMatchupState
    {
        $userState = UserMatchupState::ANONYMOUS;
        if ($user !== null) {
            $userState = UserMatchupState::NEW_USER;
            $coasterAffinityCount = $this->coasterAffinityRepository->count(['user' => $user]);
            if ($coasterAffinityCount > 10) {
                $userState = UserMatchupState::ESTABLISHED;
            }
        }

        return $userState;
    }

    private function determineAnchorCoaster(UserMatchupState $userState, ?User $user = null): Coaster
    {
        $anchorCoaster = null;

        // 1. High-confidence UserCoasterAffinity coaster (if user exists)
        if ($userState === UserMatchupState::ESTABLISHED) {
            $affinities = $this->coasterAffinityRepository->findHighestConfidenceCoastersForUser($user);
            shuffle($affinities);

            $anchorCoaster = $affinities[0]->getCoaster();
            $this->matchupLogger->debug('Anchor selected via High-confidence UserCoasterAffinity', [
                'coaster_id' => $anchorCoaster->getId(),
            ]);
        }

        // 2. Popular coaster in user's country (if known)
        if ($anchorCoaster === null) {
            $topCountryCoasters = $this->coasterRepository->findCoastersByLocation(
            // TODO: implement real country detection
                $this->determineLocalizationBaseOndRequest()
            );

            shuffle($topCountryCoasters);

            if (count($topCountryCoasters) > 0) {
                $anchorCoaster = $topCountryCoasters[0];
                $this->matchupLogger->debug('Anchor selected via Popular coaster in user\'s country', [
                    'coaster_id' => $anchorCoaster->getId(),
                ]);
            }
        }

        // 3. Globally high-rated coaster (top X% Elo)
        if ($anchorCoaster === null) {
            $topCoasters = $this->coasterRepository->findTopRated();

            shuffle($topCoasters);

            if (count($topCoasters) > 0) {
                $anchorCoaster = $topCoasters[0];
                $this->matchupLogger->debug('Anchor selected via Globally high-rated coaster', [
                    'coaster_id' => $anchorCoaster->getId(),
                ]);
            }
        }

        // 4. Random popular fallback
        if ($anchorCoaster === null) {
            // TODO: make list of top ten rollercoasters 2025 or use db entry 1 if they are not available
            $this->matchupLogger->error('No anchor coaster found');
            throw new LogicException('No anchor coaster found.');
        }

        return $anchorCoaster;
    }

    private function determineChallengerCoaster(Coaster $anchorCoaster, float $temperature, ?User $user): ?Coaster
    {
        $candidates = [];

        $similarElo = $this->challengeCoasterPoolProvider->getCoasterBySimilarEloRating($anchorCoaster);
        $lowComparison = $this->challengeCoasterPoolProvider->getCoasterByLowComparisonCount();
        $sameCountry = $this->challengeCoasterPoolProvider->getCoasterFromSameCountry($anchorCoaster);
        $randomLocation = $this->challengeCoasterPoolProvider->getCoasterByRandomLocationType($anchorCoaster);

        $this->matchupLogger->debug('Challenger candidates pool sizes', [
            'similarElo' => count($similarElo),
            'lowComparison' => count($lowComparison),
            'sameCountry' => count($sameCountry),
            'randomLocation' => count($randomLocation),
        ]);

        $candidates[] = $similarElo;
        $candidates[] = $lowComparison;
        $candidates[] = $sameCountry;
        $candidates[] = $randomLocation;

        // Flatten array of arrays
        $candidates = array_merge(...array_filter($candidates));

        // Deduplicate by coaster ID
        $candidates = $this->deduplicateCoasters($candidates, $anchorCoaster);

        $this->matchupLogger->debug('Total unique challenger candidates', ['count' => count($candidates)]);

        if (count($candidates) === 0) {
            return null;
        }

        // Score
        $scored = [];
        foreach ($candidates as $candidate) {
            $score = $this->challengeScoreCalculator->calculateChallengerScore($anchorCoaster, $candidate, $user);
            $scored[] = [
                'coaster' => $candidate,
                'score' => $score,
            ];

            $this->matchupLogger->debug('Scored candidate', [
                'id' => $candidate->getId(),
                'name' => $candidate->getName(),
                'score' => $score,
            ]);
        }

        return $this->challengeScoreCalculator->sampleByScore($scored, $temperature);
    }

    /**
     * @return array<Coaster>
     */
    private function deduplicateCoasters(array $coasters, Coaster $anchorCoaster): array
    {
        $unique = [];
        foreach ($coasters as $coaster) {
            if ($coaster->getId() === $anchorCoaster->getId()) {
                continue;
            }
            $unique[$coaster->getId()] = $coaster;
        }

        return array_values($unique);
    }
}
