<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CollectionController extends AbstractController
{
    #[Route('/collection', name: 'app_collection')]
    public function index(Request $request): Response
    {
        $columns = $request->query->getInt('columns', 2);
        if (!in_array($columns, [1, 2, 4], true)) {
            $columns = 2;
        }

        return $this->render('collection/collection.html.twig', [
            'columns' => $columns,
        ]);
    }
}
