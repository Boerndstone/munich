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
        try {
            return $this->doAutocomplete($request, $rockRepository, $routesRepository);
        } catch (\Throwable $e) {
            $message = $this->getParameter('kernel.environment') === 'dev'
                ? $e->getMessage()
                : 'Ein Fehler ist aufgetreten.';
            return $this->json(['rocks' => [], 'routes' => [], 'routesHtml' => '', 'searchMode' => 'name', '_error' => $message], 500);
        }
    }

    private function doAutocomplete(Request $request, RockRepository $rockRepository, RoutesRepository $routesRepository): JsonResponse
    {
        $query = $request->query->get('query', '');
        $mode = $request->query->get('mode', 'name');
        $selectedGrades = $request->query->all('grades') ?? [];
        $selectedArea = $request->query->get('area', '');
        $childFriendly = filter_var($request->query->get('childFriendly', false), \FILTER_VALIDATE_BOOLEAN);
        $sunny = filter_var($request->query->get('sunny', false), \FILTER_VALIDATE_BOOLEAN);
        $rainProtected = filter_var($request->query->get('rainProtected', false), \FILTER_VALIDATE_BOOLEAN);
        $train = filter_var($request->query->get('train', false), \FILTER_VALIDATE_BOOLEAN);
        $bike = filter_var($request->query->get('bike', false), \FILTER_VALIDATE_BOOLEAN);

        // Attributes search – child friendly, sunny, rain protected, train, bike
        if ($mode === 'attributes') {
            $filters = array_filter([
                'childFriendly' => $childFriendly,
                'sunny' => $sunny,
                'rainProtected' => $rainProtected,
                'train' => $train,
                'bike' => $bike,
            ]);
            if (empty($filters)) {
                return $this->json(['rocks' => [], 'routes' => [], 'routesHtml' => '', 'searchMode' => $mode]);
            }
            $rocks = $rockRepository->findByAttributes($filters, $selectedArea ?: null);
            $rockResults = [];
            foreach ($rocks as $rock) {
                $area = $rock->getArea();
                if ($area) {
                    $rockResults[] = [
                        'name' => $rock->getName(),
                        'url' => $this->generateUrl('show_rock', [
                            'areaSlug' => $area->getSlug(),
                            'slug' => $rock->getSlug(),
                        ]),
                    ];
                }
            }
            return $this->json(['rocks' => $rockResults, 'routes' => [], 'routesHtml' => '', 'searchMode' => $mode]);
        }

        // Grade search – paginated
        if ($mode === 'grade') {
            if (empty($selectedGrades)) {
                return $this->json(['rocks' => [], 'routes' => [], 'routesHtml' => '', 'searchMode' => $mode]);
            }
            $perPage = max(1, min(50, (int) $request->query->get('perPage', 20)));
            $page = max(1, (int) $request->query->get('page', 1));
            $totalCount = $routesRepository->countByGrades($selectedGrades, $selectedArea ?: null);
            $totalPages = max(1, (int) ceil($totalCount / $perPage));
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $perPage;
            $routes = $routesRepository->findByGrades($selectedGrades, $selectedArea ?: null, $perPage, $offset);
            $routeResults = $this->formatRoutesForJson($routes);
            return $this->json([
                'rocks' => [],
                'routes' => $routeResults,
                'routesHtml' => $this->buildRoutesTableHtml($routeResults),
                'searchMode' => $mode,
                'totalCount' => $totalCount,
                'page' => $page,
                'perPage' => $perPage,
            ]);
        }

        // Name and firstascent need at least 2 characters
        if (empty($query) || strlen($query) < 2) {
            return $this->json(['rocks' => [], 'routes' => [], 'routesHtml' => '', 'searchMode' => $mode]);
        }

        $rockResults = [];
        $routeResults = [];

        if ($mode === 'name') {
            // Search rocks and routes by name (same as /suche approach)
            $rocks = $rockRepository->search($query);
            foreach ($rocks as $rock) {
                $area = $rock->getArea();
                if ($area) {
                    $rockResults[] = [
                        'name' => $rock->getName(),
                        'url' => $this->generateUrl('show_rock', [
                            'areaSlug' => $area->getSlug(),
                            'slug' => $rock->getSlug()
                        ])
                    ];
                }
            }
            $routes = $routesRepository->search($query);
            $routeResults = $this->formatRoutesForJson($routes);
        } elseif ($mode === 'firstascent') {
            $routes = $routesRepository->findByFirstAscent($query);
            $routeResults = $this->formatRoutesForJson($routes);
        }

        return $this->json([
            'rocks' => $rockResults,
            'routes' => $routeResults,
            'routesHtml' => $this->buildRoutesTableHtml($routeResults),
            'searchMode' => $mode,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $routes Same shape as formatRoutesForJson()
     */
    private function buildRoutesTableHtml(array $routes): string
    {
        if ($routes === []) {
            return '';
        }

        return $this->renderView('components/search/routes_table_fragment.html.twig', [
            'routes' => $routes,
        ]);
    }

    /**
     * @param \App\Entity\Routes[] $routes
     * @return array<int, array{name: string, area: string, rock: string, grade: ?string, firstAscent: ?string, yearFirstAscent: ?int, url: string, rating: ?int, protection: ?int, rockQuality: ?bool}>
     */
    private function formatRoutesForJson(array $routes): array
    {
        $results = [];
        foreach ($routes as $route) {
            $results[] = [
                'name' => $route->getName(),
                'area' => $route->getArea() ? $route->getArea()->getName() : '',
                'rock' => $route->getRock() ? $route->getRock()->getName() : '',
                'grade' => $route->getGrade(),
                'firstAscent' => $route->getFirstAscent(),
                'yearFirstAscent' => $route->getYearFirstAscent(),
                'rating' => $route->getRating(),
                'protection' => $route->getProtection(),
                'rockQuality' => $route->isRockQuality(),
                'url' => $this->generateUrl('show_rock', [
                    'areaSlug' => $route->getArea() ? $route->getArea()->getSlug() : 'unknown',
                    'slug' => $route->getRock() ? $route->getRock()->getSlug() : 'unknown'
                ])
            ];
        }
        return $results;
    }
}