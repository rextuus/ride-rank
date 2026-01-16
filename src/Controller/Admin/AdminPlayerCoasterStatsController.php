<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\CoasterSelectType;
use App\Form\Admin\PlayerCoasterSelectType;
use App\Repository\CoasterRepository;
use App\Repository\PairwiseComparisonRepository;
use App\Repository\PlayerCoasterAffinityRepository;
use App\Repository\PlayerCoasterRatingRepository;
use App\Repository\RiddenCoasterRepository;
use App\Service\Statistic\CoasterPrimaryStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stats', name: 'admin_stats_')]
#[IsGranted('ROLE_ADMIN')]
class AdminPlayerCoasterStatsController extends AbstractController
{
    #[Route('/pairwise-comparisons', name: 'coaster_primary')]
    public function index(
        Request $request,
        CoasterPrimaryStatsService $statsService,
        PairwiseComparisonRepository $comparisonRepository,
    ): Response {
        $form = $this->createForm(CoasterSelectType::class);
        $form->handleRequest($request);

        $stats = null;
        $coaster = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $coaster = $form->getData()['coaster'] ?? null;

            if ($coaster) {
                $stats = $statsService->getStats($coaster);
            }
        }

        // ✅ ALWAYS load comparisons
        $comparisons = $comparisonRepository->findNewestWithOptionalCoaster(
            $coaster,
            50
        );

        return $this->render('admin/pairwise_comparisons_stats.html.twig', [
            'form' => $form->createView(),
            'coaster' => $coaster,
            'stats' => $stats,
            'comparisons' => $comparisons,
            'submitted' => $form->isSubmitted(),
        ]);
    }

    #[Route('/player-coaster', name: 'player_coaster')]
    public function playerCoasterStats(
        Request $request,
        PlayerCoasterAffinityRepository $affinityRepository,
        RiddenCoasterRepository $riddenCoasterRepository,
        PlayerCoasterRatingRepository $ratingRepository,
        PairwiseComparisonRepository $comparisonRepository,
    ): Response {
        $form = $this->createForm(PlayerCoasterSelectType::class);
        $form->handleRequest($request);

        $data = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $selection = $form->getData();
            $player = $selection['player'];
            $coaster = $selection['coaster'];

            if ($player && $coaster) {
                $affinity = $affinityRepository->findOneBy(['player' => $player, 'coaster' => $coaster]);
                $ridden = $riddenCoasterRepository->findOneBy(['player' => $player, 'coaster' => $coaster]);
                $playerRating = $ratingRepository->findOneBy(['player' => $player, 'coaster' => $coaster]);
                $comparisons = $comparisonRepository->findByPlayerAndCoaster($player, $coaster);

                $data = [
                    'player' => $player,
                    'coaster' => $coaster,
                    'affinity' => $affinity,
                    'ridden' => $ridden,
                    'playerRating' => $playerRating,
                    'comparisons' => $comparisons,
                ];
            }
        }

        return $this->render('admin/player_coaster_stats.html.twig', [
            'form' => $form->createView(),
            'data' => $data,
            'submitted' => $form->isSubmitted(),
        ]);
    }
}
