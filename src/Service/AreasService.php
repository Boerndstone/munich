<?php

namespace App\Service;

use App\Repository\AreaRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AreasService
{
    private AreaRepository $areaRepository;
    private CacheInterface $cache;

    // Cache TTL in seconds (1 hour = 3600 seconds)
    private const CACHE_TTL = 3600;

    public function __construct(AreaRepository $areaRepository, CacheInterface $cache)
    {
        $this->areaRepository = $areaRepository;
        $this->cache = $cache;
    }

    /**
     * Get areas information for the main page (with rock counts, route counts, etc.)
     * Results are cached for better performance
     */
    public function getAreasInformation(): array
    {
        return $this->cache->get('areas_information', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);
            
            return $this->areaRepository->getAreasInformation();
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