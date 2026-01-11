<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CollectionController extends AbstractController
{
    // src/Controller/CollectionController.php
    #[Route('/collection', name: 'app_collection')]
    public function index(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $columns = $request->query->getInt('columns', 2);
        if (!in_array($columns, [1, 2, 4])) {
            $columns = 2;
        }

        $page = $request->query->getInt('page', 1);
        $limit = 16; // Beispiel-Limit pro Album-Seite

        $coasters = [];
        // Erzeuge mehr Testdaten für Paginierung
        $baseCoasters = [
            [
                'name' => 'Blue Fire',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766314/CoasterMatch/Europa%20Park/Europa_Park_Blue_Fire_w9rn63.png',
                'ridden' => true,
                'rating' => 4
            ],
            [
                'name' => 'Voltron Nevera',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Voltron_kasjwc.png',
                'ridden' => false,
                'rating' => 5
            ],
            [
                'name' => 'Wodan Timburcoaster',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766316/CoasterMatch/Europa%20Park/Europa_Park_Wodan_c6utyt.png',
                'ridden' => true,
                'rating' => 3
            ],
            [
                'name' => 'Taron',
                'park' => 'Phantasialand',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766318/CoasterMatch/Europa%20Park/Europa_Park_Super_Splash_eykgpf.png',
                'ridden' => false,
                'rating' => 5
            ],
            [
                'name' => 'Black Mamba',
                'park' => 'Phantasialand',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766317/CoasterMatch/Europa%20Park/Europa_Park_Euro_Sat_zbbtsg.png',
                'ridden' => true,
                'rating' => 4
            ],
            [
                'name' => 'Expedition GeForce',
                'park' => 'Holiday Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/dl4y4cfvs/image/upload/v1757766314/CoasterMatch/Europa%20Park/Europa_Park_Matterhorn_Blitz_imsx5o.png',
                'ridden' => false,
                'rating' => 5
            ],
        ];

        for ($i = 0; $i < 40; $i++) {
            $c = $baseCoasters[$i % count($baseCoasters)];
            $c['name'] .= ' ' . ($i + 1);
            $coasters[] = $c;
        }

        $totalCount = count($coasters);
        $totalPages = (int) ceil($totalCount / $limit);
        $offset = ($page - 1) * $limit;
        $pagedCoasters = array_slice($coasters, $offset, $limit);

        // Gruppierung
        $coastersByPark = [];
        foreach ($pagedCoasters as $c) {
            $coastersByPark[$c['park']][] = $c;
        }

        return $this->render('collection/collection.html.twig', [
            'coastersByPark' => $coastersByPark,
            'columns' => $columns,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

}
