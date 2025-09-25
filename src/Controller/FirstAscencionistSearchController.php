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
        $selectedGrades = $request->query->all('grades') ?? [];
        $searchType = $request->query->get('search_type', 'firstascent'); // 'firstascent' or 'grade'
        $page = (int) $request->query->get('page', 1);
        $routes = [];
        $totalRoutes = 0;
        $totalPages = 0;

        // Define available grade ranges for checkboxes
        $gradeRanges = [
            '1' => ['label' => '1'],
            '2' => ['label' => '2'],
            '3' => ['label' => '3'],
            '4' => ['label' => '4'],
            '5' => ['label' => '5'],
            '6' => ['label' => '6'],
            '7' => ['label' => '7'],
            '8' => ['label' => '8'],
            '9' => ['label' => '9'],
            '10' => ['label' => '10'],
            '11' => ['label' => '11'],
        ];

        if ($searchType === 'firstascent' && !empty($query)) {
            // Search for routes by firstAscent name only (no pagination)
            $routes = $routesRepository->findByFirstAscent($query);
            $totalRoutes = count($routes);
        } elseif ($searchType === 'grade' && !empty($selectedGrades)) {
            // Search for routes by grade ranges only (with pagination)
            $routes = $routesRepository->findByGrades($selectedGrades);
            $totalRoutes = count($routes);
            
            // Calculate pagination only for grade search
            $itemsPerPage = 30;
            $totalPages = ceil($totalRoutes / $itemsPerPage);
            $offset = ($page - 1) * $itemsPerPage;
            
            // Apply pagination to routes
            if (!empty($routes)) {
                $routes = array_slice($routes, $offset, $itemsPerPage);
            }
        }

        return $this->render('frontend/suche.html.twig', [
            'query' => $query,
            'routes' => $routes,
            'selectedGrades' => $selectedGrades,
            'gradeRanges' => $gradeRanges,
            'searchType' => $searchType,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRoutes' => $totalRoutes,
        ]);
    }
}