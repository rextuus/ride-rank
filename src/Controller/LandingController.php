<?php

namespace App\Controller;

use App\Repository\CoasterRepository;
use App\Repository\UserCoasterAffinityRepository;
use App\Service\Rating\MatchupSelectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LandingController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(MatchupSelectionService $coasterAffinityRepository): Response
    {
//        dd($coasterAffinityRepository->getNextMatchup(null));

        return $this->render('landing/home.html.twig', [
            'controller_name' => 'LandingController',
        ]);
    }
}
