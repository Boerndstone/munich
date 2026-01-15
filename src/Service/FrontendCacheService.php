<?php

namespace App\Service;

use App\Repository\CommentRepository;
use App\Repository\RockRepository;
use App\Repository\RoutesRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for caching frontend data that doesn't change frequently
 */
class FrontendCacheService
{
    private RoutesRepository $routesRepository;
    private CommentRepository $commentRepository;
    private RockRepository $rockRepository;
    private CacheInterface $cache;

    // Cache TTL in seconds
    private const LATEST_ROUTES_TTL = 1800;   // 30 minutes
    private const LATEST_COMMENTS_TTL = 600;  // 10 minutes (comments change more often)
    private const BANNED_ROCKS_TTL = 3600;    // 1 hour
    private const AREA_ROCKS_TTL = 3600;      // 1 hour
    private const TOP100_ROUTES_TTL = 3600;   // 1 hour

    public function __construct(
        RoutesRepository $routesRepository,
        CommentRepository $commentRepository,
        RockRepository $rockRepository,
        CacheInterface $cache
    ) {
        $this->routesRepository = $routesRepository;
        $this->commentRepository = $commentRepository;
        $this->rockRepository = $rockRepository;
        $this->cache = $cache;
    }

    /**
     * Get latest routes with caching
     */
    public function getLatestRoutes(): array
    {
        return $this->cache->get('frontend_latest_routes', function (ItemInterface $item): array {
            $item->expiresAfter(self::LATEST_ROUTES_TTL);
            
            return $this->routesRepository->latestRoutes();
        });
    }

    /**
     * Get latest comments with caching
     */
    public function getLatestComments(): array
    {
        return $this->cache->get('frontend_latest_comments', function (ItemInterface $item): array {
            $item->expiresAfter(self::LATEST_COMMENTS_TTL);
            
            return $this->commentRepository->latestComments();
        });
    }

    /**
     * Get seasonally banned rocks with caching
     */
    public function getBannedRocks(): array
    {
        return $this->cache->get('frontend_banned_rocks', function (ItemInterface $item): array {
            $item->expiresAfter(self::BANNED_ROCKS_TTL);
            
            return $this->rockRepository->saisonalGesperrt();
        });
    }

    /**
     * Clear only the comments cache (useful when new comments are added)
     */
    public function clearCommentsCache(): void
    {
        $this->cache->delete('frontend_latest_comments');
    }

    /**
     * Clear only the routes cache (useful when new routes are added)
     */
    public function clearRoutesCache(): void
    {
        $this->cache->delete('frontend_latest_routes');
    }

    /**
     * Get rocks information for an area with caching
     */
    public function getRocksForArea(string $areaSlug): array
    {
        $cacheKey = 'area_rocks_' . $areaSlug;
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($areaSlug): array {
            $item->expiresAfter(self::AREA_ROCKS_TTL);
            
            return $this->rockRepository->getRocksInformation($areaSlug);
        });
    }

    /**
     * Get route grades for rocks in an area with caching
     */
    public function getRouteGradesForArea(string $areaSlug): array
    {
        $cacheKey = 'area_route_grades_' . $areaSlug;
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($areaSlug): array {
            $item->expiresAfter(self::AREA_ROCKS_TTL);
            
            return $this->rockRepository->getRouteGradesForRocks($areaSlug);
        });
    }

    /**
     * Get top 100 routes for an area with caching
     */
    public function getTop100RoutesForArea(int $areaId): array
    {
        $cacheKey = 'area_top100_routes_' . $areaId;
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($areaId): array {
            $item->expiresAfter(self::TOP100_ROUTES_TTL);
            
            return $this->routesRepository->findTop100ByAreaCached($areaId);
        });
    }

    /**
     * Clear all frontend cache including area-specific caches
     */
    public function clearCache(): void
    {
        $this->cache->delete('frontend_latest_routes');
        $this->cache->delete('frontend_latest_comments');
        $this->cache->delete('frontend_banned_rocks');
        // Note: Area-specific caches will expire naturally or can be cleared individually
    }

    /**
     * Clear cache for a specific area
     */
    public function clearAreaCache(string $areaSlug, int $areaId): void
    {
        $this->cache->delete('area_rocks_' . $areaSlug);
        $this->cache->delete('area_route_grades_' . $areaSlug);
        $this->cache->delete('area_top100_routes_' . $areaId);
    }
}

