<?php

namespace App\Controller;

use App\Common\Entity\Enum\LocationType;
use App\Entity\User;
use App\Repository\CoasterRepository;
use App\Repository\RiddenCoasterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CollectionController extends AbstractController
{
    #[Route('/collection', name: 'app_collection')]
    public function index(
        Request $request,
        RiddenCoasterRepository $riddenRepo,
        CoasterRepository $coasterRepo
    ): Response {
        $columns = $request->query->getInt('columns', 2);
        if (!in_array($columns, [1, 2, 4], true)) {
            $columns = 2;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // 1. User-specific data
        $riddenCoasters = $riddenRepo->getRiddenCoasterOfUser($user);

        // 2. Global context
        $maxElo = $coasterRepo->getMaxElo();
        $globalRanks = $coasterRepo->getGlobalRanks();

        $unlockedParks = [];

        foreach ($riddenCoasters as $ridden) {
            $coaster = $ridden->getCoaster();
            $park = $coaster
                ->getFirstLocationOfType(LocationType::AMUSEMENT_PARK)
                ?->getIdent();

            if ($park) {
                $unlockedParks[$park] = true;
            }
        }

        $unlockedParks = array_keys($unlockedParks);

        $allParkCoasters = $coasterRepo->findByParks($unlockedParks);

        $coastersByPark = [];

        $riddenMap = [];
        foreach ($riddenCoasters as $ridden) {
            $riddenMap[$ridden->getCoaster()->getId()] = true;
        }

        foreach ($allParkCoasters as $coaster) {
            $park = $coaster
                ->getFirstLocationOfType(LocationType::AMUSEMENT_PARK)
                ?->getName() ?? 'Unknown park';

            $elo = (int) round($coaster->getRating());
            $eloPercent = $maxElo > 0
                ? (int) round(($elo / $maxElo) * 100)
                : 0;

            $isRidden = isset($riddenMap[$coaster->getId()]);

            $coastersByPark[$park][] = [
                'id'       => $coaster->getId(),
                'name'       => $coaster->getName() ?? 'Unknown',
                'park'       => $park,
                'country'    => $coaster
                    ->getFirstLocationOfType(LocationType::AMUSEMENT_PARK)
                    ?->getName(),
                'image'      => $coaster->getCdnImageUrl()
                    ?? $coaster->getRcdbImageUrl(),
                'ridden'     => $isRidden,
                'elo'        => $elo,
                'eloPercent' => $isRidden ? $eloPercent : 0,
                'rank'       => $globalRanks[$coaster->getId()] ?? null,
            ];
        }


        return $this->render('collection/collection.html.twig', [
            'coastersByPark' => $coastersByPark,
            'columns'        => $columns,
        ]);
    }
}

