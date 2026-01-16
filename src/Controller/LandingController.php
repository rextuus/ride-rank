<?php

namespace App\Controller;

use App\Repository\CoasterRepository;
use App\Repository\PlayerCoasterAffinityRepository;
use App\Service\Rating\MatchupSelectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LandingController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CoasterRepository $coasterRepository, EntityManagerInterface $entityManager): Response
    {
//        dd($coasterAffinityRepository->getNextMatchup(null));

//        $coasters = $coasterRepository->findAll();
//        foreach ($coasters as $coaster) {
//            $coaster->setSelectionSeed(random_int(1, 10000));
//            $entityManager->persist($coaster);
//        }
//        $entityManager->flush();
//        dd();

        return $this->render('landing/home.html.twig', [
            'controller_name' => 'LandingController',
        ]);
    }
}
