<?php

namespace App\Service;

use App\Repository\AreaRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AreasService
{
    // Cache TTL in seconds (1 hour = 3600 seconds)
    private const CACHE_TTL = 3600;

    /**
     * Maximum number of external travel time requests to perform
     * during a single areas_information cache computation.
     */
    private const MAX_TRAVELTIME_REQUESTS = 20;

    public function __construct(
        private AreaRepository $areaRepository,
        private CacheInterface $cache,
        private TravelTimeService $travelTimeService,
    ) {
    }

    /**
     * Get areas information for the main page (with rock counts, route counts, travel time from Munich, etc.)
     * Results are cached for better performance
     */
    public function getAreasInformation(): array
    {
        return $this->cache->get('areas_information', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            $areas = $this->areaRepository->getAreasInformation();

            $travelTimeRequests = 0;

            foreach ($areas as &$area) {
                $lat = isset($area['lat']) ? (float) $area['lat'] : null;
                $lng = isset($area['lng']) ? (float) $area['lng'] : null;

                // If coordinates are missing, we cannot compute travel time
                if ($lat === null || $lng === null) {
                    $area['travelTimeMinutes'] = null;
                    continue;
                }

                // Protect against excessive external calls on a cold cache
                if ($travelTimeRequests >= self::MAX_TRAVELTIME_REQUESTS) {
                    $area['travelTimeMinutes'] = null;
                    continue;
                }

                try {
                    $minutes = $this->travelTimeService->getDrivingMinutesFromMunich($lng, $lat);
                    $area['travelTimeMinutes'] = $minutes;
                } catch (\Throwable $e) {
                    // On failure, fall back to null to avoid blocking cache computation
                    $area['travelTimeMinutes'] = null;
                }

                $travelTimeRequests++;
            }

            return $areas;
        });
    }

    /**
     * Get areas for footer navigation
     * Results are cached for better performance
     */
    public function getFooterAreas(): array
    {
        return $this->cache->get('areas_footer', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);
            
            return $this->areaRepository->getAreasFooter();
        });
    }

    /**
     * Get areas for sidebar navigation
     * Results are cached for better performance
     */
    public function getSidebarAreas(): array
    {
        return $this->cache->get('areas_sidebar', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);
            
            return $this->areaRepository->sidebarNavigation();
        });
    }

    /**
     * Get all areas with basic information
     */
    public function getAllAreas(): array
    {
        return $this->areaRepository->findAll();
    }

    /**
     * Get area by slug
     */
    public function getAreaBySlug(string $slug): ?object
    {
        return $this->areaRepository->findOneBy(['slug' => $slug]);
    }

    /**
     * Clear all areas-related cache
     * Call this when area data is updated in the admin
     */
    public function clearCache(): void
    {
        $this->cache->delete('areas_information');
        $this->cache->delete('areas_footer');
        $this->cache->delete('areas_sidebar');
    }
} 