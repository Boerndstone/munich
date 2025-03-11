<?php
// src/Controller/SearchController.php
namespace App\Controller;

use App\Repository\RockRepository;
use App\Repository\RoutesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search')]
    public function index(Request $request, RockRepository $rockRepository, RoutesRepository $routeRepository, LoggerInterface $logger): Response
    {
        try {
            $query = $request->query->get('query');
            if (empty($query)) {
                return new JsonResponse([]);
            }

            $logger->info('Search query: ' . $query);

            $rocks = $rockRepository->search($query);
            $routes = $routeRepository->search($query);

            // Convert the search results to an array of arrays
            $rocksArray = array_map(function ($rock) {
                $area = $rock->getArea();
                return [
                    'id' => $rock->getId(),
                    'name' => $rock->getName(),
                    'url' => $area ? $this->generateUrl('show_rock', ['areaSlug' => $area->getSlug(), 'slug' => $rock->getSlug()]) : null,
                ];
            }, $rocks);

            // Convert the route search results to an array of arrays
            $routesArray = array_map(function ($route) {
                $rock = $route->getRock();
                $area = $rock ? $rock->getArea() : null;
                return [
                    'id' => $route->getId(),
                    'name' => $route->getName(),
                    'rock' => $rock->getName(),
                    'area' => $area->getName(),
                    'url' => $area ? $this->generateUrl('show_rock', ['areaSlug' => $area->getSlug(), 'slug' => $rock->getSlug()]) : null,
                ];
            }, $routes);

            // dd($routesArray);

            $results = [
                'rocks' => $rocksArray,
                'routes' => $routesArray,
            ];

            return new JsonResponse($results);
        } catch (\Exception $e) {
            $logger->error('Search error: ' . $e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
