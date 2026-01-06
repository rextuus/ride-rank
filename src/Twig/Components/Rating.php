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
    public float $value = 0;

    #[LiveProp]
    public int $max = 5;

    #[LiveProp]
    public bool $interactive = false;
}
