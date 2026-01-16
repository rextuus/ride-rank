<?php

declare(strict_types=1);

namespace App\Service\PlayerRating;

use App\Entity\Coaster;

class EloCoasterDto
{
    public function __construct(
        public Coaster $coaster,
        public int $wins,
        public int $losses,
        public int $personalWins,
        public int $personalLosses,
        public ?float $personalRating = null,
    )
    {
    }
}
