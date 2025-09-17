<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Rating
{
    use DefaultActionTrait;

    #[LiveProp]
    public float $value = 0; // Durchschnitt, eigene Bewertung etc.

    #[LiveProp]
    public int $max = 5; // Anzahl Sterne

    #[LiveProp]
    public bool $interactive = false;
}
