<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RankingController extends AbstractController
{
    #[Route('/ranking', name: 'app_ranking')]
    public function index(): Response
    {
        $ranking = [
            [
                'name' => 'Blue Fire',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766314/CoasterMatch/Europa%20Park/Europa_Park_Blue_Fire_w9rn63.png',
                'ridden' => true,
                'avgRating' => 4.2,
                'ownRating' => 4,
                'wins' => 32,
                'losses' => 14,
            ],
            [
                'name' => 'Voltron Nevera',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Voltron_kasjwc.png',
                'ridden' => true,
                'avgRating' => 4.5,
                'ownRating' => 5,
                'wins' => 40,
                'losses' => 10,
            ],
            [
                'name' => 'Wodan Timburcoaster',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Wodan_c6utyt.png',
                'ridden' => false,
                'avgRating' => 3.9,
                'ownRating' => 3,
                'wins' => 18,
                'losses' => 22,
            ],
            [
                'name' => 'Dragon’s Fury',
                'park' => 'Chessington World of Adventures',
                'country' => 'UK',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766315/CoasterMatch/Europa%20Park/Europa_Park_Schweizer_Bobbahn_rpqoxh.png',
                'ridden' => true,
                'avgRating' => 3.5,
                'ownRating' => 2,
                'wins' => 12,
                'losses' => 30,
            ],
        ];

        return $this->render('ranking/ranking.html.twig', [
            'ranking' => $ranking,
        ]);
    }
}
