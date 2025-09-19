<?php

namespace App\Controller;

use App\Repository\RoutesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FirstAscencionistSearchController extends AbstractController
{
    #[Route('/suche', name: 'app_suche')]
    public function index(Request $request, RoutesRepository $routesRepository): Response
    {
        $query = $request->query->get('q', '');
        $routes = [];

        if (!empty($query)) {
            // Search for routes by firstAscent string field
            $routes = $routesRepository->findByFirstAscent($query);
        }

        return $this->render('frontend/suche.html.twig', [
            'query' => $query,
            'routes' => $routes,
        ]);
    }
}