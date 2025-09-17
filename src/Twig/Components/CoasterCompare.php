<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;

#[AsLiveComponent]
class CoasterCompare
{
    use DefaultActionTrait;

    #[LiveProp]
    public array $left = [];

    #[LiveProp]
    public array $right = [];

    public function mount(): void
    {
        $this->pickNewCoasters();
    }

    #[LiveAction]
    public function chooseLeft(): void
    {
        $this->pickNewCoasters();
    }

    #[LiveAction]
    public function chooseRight(): void
    {
        $this->pickNewCoasters();
    }

    #[LiveAction]
    public function skip(): void
    {
        $this->pickNewCoasters();
    }

    private function pickNewCoasters(): void
    {
        $coasters = [
            ['name' => 'Wodan Timburcoaster', 'park' => 'Europa Park', 'country' => 'Deutschland', 'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Wodan_c6utyt.png'],
            ['name' => 'Voltron Nevera', 'park' => 'Europa Park', 'country' => 'Deutschland', 'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Voltron_kasjwc.png'],
            ['name' => 'Blue Fire', 'park' => 'Europa Park', 'country' => 'Deutschland', 'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766314/CoasterMatch/Europa%20Park/Europa_Park_Blue_Fire_w9rn63.png'],
            ['name' => 'Schweizer Bobbahn', 'park' => 'Europa Park', 'country' => 'Deutschland', 'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766315/CoasterMatch/Europa%20Park/Europa_Park_Schweizer_Bobbahn_rpqoxh.png'],
        ];

        shuffle($coasters);
        $this->left = $coasters[0];
        $this->right = $coasters[1];
    }
}

