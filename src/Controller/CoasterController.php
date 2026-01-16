<?php

namespace App\Controller;

use App\Entity\Coaster;
use App\Repository\CoasterRepository;
use App\Service\Util\CoasterNormalizer;
use App\Service\Util\UnitConversionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CoasterController extends AbstractController
{
    public bool $useMetricUnits = true;

    public function __construct(private readonly CoasterNormalizer $coasterNormalizer,
    )
    {
    }


    #[Route('/coaster/{coaster}', name: 'app_coaster_show')]
    public function show(Coaster $coaster): Response
    {
        $coaster = $this->coasterNormalizer->normalize($coaster);

        return $this->render('coaster/detail.html.twig', [
            'coaster' => $coaster,
        ]);
    }
}
