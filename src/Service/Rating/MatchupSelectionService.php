<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Entity\Player;
use App\Repository\CoasterRepository;
use App\Repository\PairwiseComparisonRepository;
use LogicException;

readonly class MatchupSelectionService
{
    public function __construct(
        private PairwiseComparisonRepository $pairwiseComparisonRepository,
        private ChallengeCandidateSelector $candidateSelector,
        private AnchorCoasterSelector $anchorCoasterSelector,
    ) {
    }

    public function getNextMatchup(Player $player): array
    {
        $playerState = $this->determinePlayerState($player);

        $anchor = $this->anchorCoasterSelector->selectAnchorCoaster($player);

        $temperature = match ($playerState) {
            PlayerMatchupState::NEW_PLAYER => 1.4,
            PlayerMatchupState::ESTABLISHED => 1.0,
            default => 0.6,
        };

        $challenger = $this->candidateSelector->selectChallengerCoaster($anchor, $temperature, $player);

        if ($challenger === null || $challenger->getId() === $anchor->getId()) {
            throw new LogicException('Invalid matchup generated');
        }

        return [$anchor, $challenger];
    }

    private function determinePlayerState(Player $player): PlayerMatchupState
    {
        $count = $this->pairwiseComparisonRepository
            ->count(['player' => $player]);

        return match (true) {
            $count === 0 => PlayerMatchupState::NEW_PLAYER,
            $count > 10 => PlayerMatchupState::ESTABLISHED,
            default => PlayerMatchupState::CASUAL,
        };
    }
}
