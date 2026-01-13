<?php

namespace App\Controller;

use App\Entity\Coaster;
use App\Service\ImageTransformation\CoasterImageTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/coaster')]
final class AdminCoasterImageController extends AbstractController
{
    #[Route('/{id}/images', name: 'admin_coaster_images')]
    public function images(
        Coaster $coaster,
        Request $request,
        CoasterImageTransformer $imageTransformer
    ): Response {
        $metadata = $coaster->getMetadata();

        if (!$metadata || !$metadata->getImages()) {
            $this->addFlash('warning', 'No images found for this coaster.');
        }

        if ($request->isMethod('POST')) {
            $imageUrl = $request->request->get('image');

            if ($imageUrl) {
                $resultPath = $imageTransformer->transform($coaster, $imageUrl);

                $this->addFlash(
                    'success',
                    'Image transformed successfully: ' . $resultPath
                );

                return $this->redirectToRoute(
                    'admin_coaster_images',
                    ['id' => $coaster->getId()]
                );
            }
        }

        $images = [];
        if ($metadata?->getImages()) {
            foreach ($metadata->getImages()['pictures'] as $img) {
                // If the array has a 'url' key
                if (is_array($img) && isset($img['url'])) {
                    $images[] = 'https://rcdb.com' . $img['url'];
                } elseif (is_string($img)) {
                    $images[] = $img;
                }
            }
        }

        return $this->render('admin/coaster/images.html.twig', [
            'coaster' => $coaster,
            'images'  => $images,
        ]);
    }
}
