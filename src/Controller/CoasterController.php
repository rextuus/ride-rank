<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CoasterController extends AbstractController
{
    #[Route('/coaster/{id}', name: 'app_coaster_show')]
    public function show(int $id): Response
    {
        $coaster = [
            'id' => $id,
            'name' => 'Blue Fire',
            'park' => 'Europa Park',
            'country' => 'Deutschland',
            'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766314/CoasterMatch/Europa%20Park/Europa_Park_Blue_Fire_w9rn63.png',
            'ridden' => true,
            'manufacturer' => 'Mack Rides',
            'type' => 'Launch Coaster',
            'drive' => 'LSM Launch',
            'inversions' => 4,
            'length' => '1056 m',
            'height' => '38 m',
            'year' => 2009,
            // ✨ neu für Ratings:
            'avgRating' => 4.2,
            'userRating' => 3.8,
            'avgCategories' => [
                'Intensität' => 4,
                'Theming' => 5,
                'Spaß' => 4,
                'Gesamteindruck' => 3,
            ],
            'userCategories' => [
                'Intensität' => 1,
                'Theming' => 2,
                'Spaß' => 4,
                'Gesamteindruck' => 1,
            ]
        ];

        return $this->render('coaster/detail.html.twig', [
            'coaster' => $coaster,
        ]);
    }

}
