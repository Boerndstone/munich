<?php

namespace App\Service;

use App\Repository\AreaRepository;
use App\Repository\RockRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AreasService
{
    // Cache TTL in seconds (1 hour = 3600 seconds)
    private const CACHE_TTL = 3600;

    public function __construct(
        private AreaRepository $areaRepository,
        private RockRepository $rockRepository,
        private CacheInterface $cache,
    ) {
    }

    /**
     * Get areas information for the main page (with rock counts, route counts, travel time from Munich, etc.)
     * Travel times come from the database (filled via app:travel-time:import). Results are cached.
     */
    public function getAreasInformation(): array
    {
        return $this->cache->get('areas_information_v2', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->areaRepository->getAreasInformation();
        });
    }

    /**
     * Online rocks with lat/lng for the main map (cached with areas data TTL).
     *
     * @return list<array<string, mixed>>
     */
    public function getMainMapRocks(): array
    {
        return $this->cache->get('main_map_rocks_v2', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->rockRepository->findOnlineRocksWithCoordinatesForMap();
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
     * Lightweight list of sidebar areas without nested rocks.
     */
    public function getSidebarAreaSummaries(): array
    {
        return $this->cache->get('areas_sidebar_summaries', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->areaRepository->sidebarAreaSummaries();
        });
    }

    /**
     * @return array<int, array{name: string, slug: string, areaSlug: string}>
     */
    public function getSidebarRocks(string $areaSlug): array
    {
        $epoch = $this->cache->get('areas_sidebar_rocks_epoch', function (ItemInterface $item): int {
            $item->expiresAfter(31536000); // 1 year
            return time();
        });

        $cacheKey = sprintf('areas_sidebar_rocks_%d_%s', $epoch, $areaSlug);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($areaSlug): array {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->rockRepository->findSidebarRocksByAreaSlug($areaSlug);
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
        $this->cache->delete('areas_information_v2');
        $this->cache->delete('areas_information');
        $this->cache->delete('areas_footer');
        $this->cache->delete('areas_sidebar');
        $this->cache->delete('areas_sidebar_summaries');
        $this->cache->delete('main_map_rocks_v2');
        $this->cache->delete('main_map_rocks_v1');
    }
}
