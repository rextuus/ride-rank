<?php

namespace App\Twig\Components;

use App\Repository\CoasterRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class EloRating
{
    public float $rating;
    public ?float $personalRating = null;

    public function __construct(
        private readonly CoasterRepository $coasterRepository
    ) {
    }

    public function getTransformedRating(): float
    {
        return $this->transform($this->rating);
    }

    public function getTransformedPersonalRating(): ?float
    {
        if ($this->personalRating === null) {
            return null;
        }

        return $this->transform($this->personalRating);
    }

    private function transform(float $elo): float
    {
        $maxElo = $this->coasterRepository->getMaxElo();
        
        if ($maxElo <= 0) {
            return 0;
        }

        // Linear transformation: (elo / maxElo) * 5
        $value = ($elo / $maxElo) * 5;
        
        return round($value, 1);
    }
}
