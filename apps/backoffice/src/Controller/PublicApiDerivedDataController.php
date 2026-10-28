<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Area;
use App\Entity\Rock;
use App\Repository\RoutesRepository;
use App\Service\AreasService;
use App\Service\FrontendCacheService;
use App\Service\RailwayAccessEstimateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public computed read models used by the Next.js frontend.
 *
 * These endpoints deliberately delegate to the same services as the legacy
 * Symfony pages. That keeps selection, sorting, and railway estimates a
 * single server-owned contract instead of duplicating business rules in a
 * client application.
 */
final class PublicApiDerivedDataController extends AbstractController
{
    /**
     * The complete, server-calculated read model for the public home page.
     *
     * This deliberately reuses the same cached queries as the Symfony page so
     * clients do not have to infer grade buckets, projects, rock counts, or
     * travel-time filters from several generic API collections.
     */
    #[Route('/api/v1/home', name: 'public_api_home', methods: ['GET'])]
    public function home(AreasService $areasService, FrontendCacheService $frontendCacheService): JsonResponse
    {
        return $this->json([
            'areas' => $areasService->getAreasInformation(),
            'rocks' => $areasService->getMainMapRocks(),
            'latestRoutes' => $frontendCacheService->getLatestRoutes(),
            'bannedRocks' => $frontendCacheService->getBannedRocks(),
        ]);
    }

    /** Public equivalent of the legacy “latest routes” page (last two years). */
    #[Route('/api/v1/latest-routes', name: 'public_api_latest_routes', methods: ['GET'])]
    public function latestRoutes(RoutesRepository $routesRepository): JsonResponse
    {
        return $this->json([
            'routes' => $routesRepository->latestRoutesPageForPublicApi((int) date('Y') - 2),
        ]);
    }

    #[Route('/api/v1/areas/{area}/top-routes', name: 'public_api_area_top_routes', methods: ['GET'], requirements: ['area' => '\\d+'])]
    public function topRoutes(Area $area, FrontendCacheService $frontendCacheService): JsonResponse
    {
        if ($area->getOnline() !== 1 || $area->getId() === null) {
            throw $this->createNotFoundException();
        }

        // FrontendCacheService delegates to RoutesRepository::findTop100ByAreaCached():
        // published rocks only, gradeNo DESC, route name ASC, maximum 100 rows.
        $routes = $frontendCacheService->getTop100RoutesForArea($area->getId());

        return $this->json([
            'routes' => $routes,
            'total' => count($routes),
            'order' => ['gradeNo' => 'DESC', 'name' => 'ASC'],
            'limit' => 100,
        ]);
    }

    #[Route('/api/v1/rocks/{rock}/railway-access', name: 'public_api_rock_railway_access', methods: ['GET'], requirements: ['rock' => '\\d+'])]
    public function railwayAccess(Rock $rock, RailwayAccessEstimateService $railwayAccessEstimateService): JsonResponse
    {
        if (!$rock->getOnline() || $rock->getArea()?->getOnline() !== 1) {
            throw $this->createNotFoundException();
        }

        return $this->json([
            'accesses' => $railwayAccessEstimateService->estimatesForRock($rock),
        ]);
    }
}
