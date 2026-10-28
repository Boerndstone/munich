<?php

namespace App\Controller;

use App\Entity\Routes;
use App\Repository\RockRepository;
use App\Repository\RoutesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public search is deliberately a small purpose-built read endpoint. API
 * Platform resources cover entity reads, while search needs to combine rocks
 * and routes and preserve the result format expected by the frontend.
 */
final class PublicApiSearchController extends AbstractController
{
    #[Route('/api/v1/search', name: 'public_api_search', methods: ['GET'])]
    public function __invoke(
        Request $request,
        RockRepository $rockRepository,
        RoutesRepository $routesRepository,
    ): JsonResponse {
        $mode = (string) $request->query->get('mode', 'name');
        $locale = (string) $request->query->get('_locale', 'de');
        $locale = in_array($locale, ['de', 'en'], true) ? $locale : 'de';
        $query = trim((string) $request->query->get('query', ''));
        $areaSlug = trim((string) $request->query->get('area', ''));

        return match ($mode) {
            'attributes' => $this->attributes($request, $rockRepository, $areaSlug),
            'grade' => $this->grades($request, $routesRepository, $areaSlug, $locale),
            'firstascent' => $this->firstAscent($query, $routesRepository, $locale),
            'name' => $this->name($query, $rockRepository, $routesRepository, $locale),
            default => $this->json([
                'rocks' => [],
                'routes' => [],
                'searchMode' => $mode,
                'error' => 'Unsupported search mode.',
            ], JsonResponse::HTTP_BAD_REQUEST),
        };
    }

    private function name(
        string $query,
        RockRepository $rockRepository,
        RoutesRepository $routesRepository,
        string $locale,
    ): JsonResponse {
        if (mb_strlen($query) < 2) {
            return $this->empty('name');
        }

        $rocks = array_map(
            fn ($rock) => [
                'name' => $rock->getName(),
                'url' => $this->rockUrl($locale, $rock->getArea()?->getSlug(), $rock->getSlug()),
            ],
            $rockRepository->search($query),
        );

        return $this->json([
            'rocks' => $rocks,
            'routes' => $this->routeResults($routesRepository->search($query), $locale),
            'searchMode' => 'name',
        ]);
    }

    private function firstAscent(string $query, RoutesRepository $routesRepository, string $locale): JsonResponse
    {
        if (mb_strlen($query) < 2) {
            return $this->empty('firstascent');
        }

        return $this->json([
            'rocks' => [],
            'routes' => $this->routeResults($routesRepository->findByFirstAscent($query), $locale),
            'searchMode' => 'firstascent',
        ]);
    }

    private function grades(Request $request, RoutesRepository $routesRepository, string $areaSlug, string $locale): JsonResponse
    {
        $grades = array_values(array_filter(
            array_map('strval', $request->query->all('grades')),
            static fn (string $grade): bool => ctype_digit($grade) && (int) $grade >= 1 && (int) $grade <= 11,
        ));
        if ($grades === []) {
            return $this->empty('grade');
        }

        $perPage = min(50, max(1, $request->query->getInt('perPage', 20)));
        $totalCount = $routesRepository->countByGrades($grades, $areaSlug ?: null);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $page = min($totalPages, max(1, $request->query->getInt('page', 1)));

        return $this->json([
            'rocks' => [],
            'routes' => $this->routeResults(
                $routesRepository->findByGrades($grades, $areaSlug ?: null, $perPage, ($page - 1) * $perPage),
                $locale,
            ),
            'searchMode' => 'grade',
            'totalCount' => $totalCount,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    private function attributes(Request $request, RockRepository $rockRepository, string $areaSlug): JsonResponse
    {
        $filters = array_filter([
            'childFriendly' => $request->query->getBoolean('childFriendly'),
            'sunny' => $request->query->getBoolean('sunny'),
            'rainProtected' => $request->query->getBoolean('rainProtected'),
            'train' => $request->query->getBoolean('train'),
            'bike' => $request->query->getBoolean('bike'),
        ]);
        if ($filters === []) {
            return $this->empty('attributes');
        }

        return $this->json([
            'rocks' => array_map(
                fn ($rock) => [
                    'name' => $rock->getName(),
                    'url' => $this->rockUrl('de', $rock->getArea()?->getSlug(), $rock->getSlug()),
                ],
                $rockRepository->findByAttributes($filters, $areaSlug ?: null),
            ),
            'routes' => [],
            'searchMode' => 'attributes',
        ]);
    }

    /** @param list<Routes> $routes */
    private function routeResults(array $routes, string $locale): array
    {
        return array_map(
            fn (Routes $route) => [
                'name' => $route->getName(),
                'area' => $route->getArea()?->getName() ?? '',
                'rock' => $route->getRock()?->getName() ?? '',
                'grade' => $route->getGrade(),
                'firstAscent' => $route->getFirstAscent(),
                'yearFirstAscent' => $route->getYearFirstAscent(),
                'rating' => $route->getRating(),
                'protection' => $route->getProtection(),
                'rockQuality' => $route->isRockQuality(),
                'url' => $this->rockUrl($locale, $route->getArea()?->getSlug(), $route->getRock()?->getSlug()),
            ],
            $routes,
        );
    }

    private function rockUrl(string $locale, ?string $areaSlug, ?string $rockSlug): string
    {
        $path = sprintf('/%s/%s', rawurlencode($areaSlug ?? 'unknown'), rawurlencode($rockSlug ?? 'unknown'));

        return $locale === 'en' ? '/en' . $path : $path;
    }

    private function empty(string $mode): JsonResponse
    {
        return $this->json(['rocks' => [], 'routes' => [], 'searchMode' => $mode]);
    }
}
