<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CoasterRepository;
use App\Repository\ParkRepository;
use App\Service\Ranking\RankingFilter;
use App\Service\Ranking\UserCoasterRatingService;
use App\Service\Util\CoasterNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RankingController extends AbstractController
{


    public function __construct(
        private readonly UserCoasterRatingService $userCoasterRatingService,
        private readonly CoasterRepository $coasterRepository,
        private readonly CoasterNormalizer $coasterNormalizer
    )
    {
    }

    #[Route('/ranking', name: 'app_ranking')]
    public function index(): Response
    {
        $ranking = [
            [
                'name' => 'Blue Fire',
                'park' => 'Europa Park',
                'country' => 'Deutschland',
                'image' => 'https://res.cloudinary.com/duzqnf8fu/image/upload/v1767691616/coasters/cartoonized/202.png',
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
                'image' => 'https://res.cloudinary.com/duzqnf8fu/image/upload/v1767691212/coasters/cartoonized/201.png',
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
                'image' => 'https://res.cloudinary.com/duzqnf8fu/image/upload/v1767616903/coasters/cartoonized/108.png',
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
                'image' => 'https://res.cloudinary.com/duzqnf8fu/image/upload/v1767692925/coasters/cartoonized/203.png',
                'ridden' => true,
                'avgRating' => 3.5,
                'ownRating' => 2,
                'wins' => 12,
                'losses' => 30,
            ],
        ];

        $user = $this->getUser();
        if ($user instanceof User) {
            $ranking = $this->userCoasterRatingService->calculateRankingByFilter(new RankingFilter(
                user: $user,
            ));
//            dd($ranking);
        }

        $coasters = $this->coasterRepository->getCoasterWithHighestElo($user);

        $ranking = [];

        foreach ($coasters as $index => $eloCoasterDto) {
            $ranking[$index] = $this->coasterNormalizer->normalize($eloCoasterDto->coaster);
            $ranking[$index]['losses'] = $eloCoasterDto->losses;
            $ranking[$index]['wins'] = $eloCoasterDto->wins;
            $ranking[$index]['personalLosses'] = $eloCoasterDto->personalLosses;
            $ranking[$index]['personalWins'] = $eloCoasterDto->personalWins;
        }

        return $this->render('ranking/ranking.html.twig', [
            'ranking' => $ranking,
        ]);
    }
}
