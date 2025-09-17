<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Ratings
{
    use DefaultActionTrait;

    #[LiveProp]
    public float $avgRating = 0;

    #[LiveProp]
    public float $userRating = 0;

    #[LiveProp]
    public array $avgCategories = [];

    #[LiveProp]
    public array $userCategories = [];

    #[LiveProp(writable: true)]
    public string $mode = 'general'; // 'general' oder 'user'

    #[LiveAction]
    public function toggle(): void
    {
        $this->mode = $this->mode === 'general' ? 'user' : 'general';
    }
}
