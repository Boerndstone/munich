<?php

namespace App\Controller;

use App\Repository\RockRepository;
use App\Repository\RoutesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search_autocomplete')]
    public function autocomplete(Request $request, RockRepository $rockRepository, RoutesRepository $routesRepository): JsonResponse
    {
        $query = $request->query->get('query', '');
        
        if (empty($query) || strlen($query) < 2) {
            return $this->json(['rocks' => [], 'routes' => []]);
        }

        // Search for rocks
        $rocks = $rockRepository->search($query);
        $rockResults = [];
        foreach ($rocks as $rock) {
            $rockResults[] = [
                'name' => $rock->getName(),
                'url' => $this->generateUrl('show_rock', [
                    'areaSlug' => $rock->getArea()->getSlug(),
                    'slug' => $rock->getSlug()
                ])
            ];
        }

        // Search for routes
        $routes = $routesRepository->search($query);
        $routeResults = [];
        foreach ($routes as $route) {
            $routeResults[] = [
                'name' => $route->getName(),
                'area' => $route->getArea() ? $route->getArea()->getName() : 'Unknown',
                'rock' => $route->getRock() ? $route->getRock()->getName() : 'Unknown',
                'url' => $this->generateUrl('show_rock', [
                    'areaSlug' => $route->getArea() ? $route->getArea()->getSlug() : 'unknown',
                    'slug' => $route->getRock() ? $route->getRock()->getSlug() : 'unknown'
                ])
            ];
        }

        return $this->json([
            'rocks' => $rockResults,
            'routes' => $routeResults
        ]);
    }
}