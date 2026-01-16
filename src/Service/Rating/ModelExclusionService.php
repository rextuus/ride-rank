<?php

declare(strict_types=1);

namespace App\Service\Rating;

use App\Entity\Player;

class ModelExclusionService
{
    private const  DEFAULT_EXCLUSIONS = [
        'Butterfly',
        'Alpine Coaster'
    ];

    public function getExcludedModelsByPlayer(Player $player): array
    {
        return self::DEFAULT_EXCLUSIONS;
    }
}
