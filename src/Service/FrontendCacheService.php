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
     * Clear all frontend cache
     */
    public function clearCache(): void
    {
        $this->cache->delete('frontend_latest_routes');
        $this->cache->delete('frontend_latest_comments');
        $this->cache->delete('frontend_banned_rocks');
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
}

