<?php

namespace App\Service;

use App\Repository\AreaRepository;

class AreasService
{
    private AreaRepository $areaRepository;

    public function __construct(AreaRepository $areaRepository)
    {
        $this->areaRepository = $areaRepository;
    }

    /**
     * Get areas information for the main page (with rock counts, route counts, etc.)
     */
    public function getAreasInformation(): array
    {
        return $this->areaRepository->getAreasInformation();
    }

    /**
     * Get areas for footer navigation
     */
    public function getFooterAreas(): array
    {
        return $this->areaRepository->getAreasFooter();
    }

    /**
     * Get areas for sidebar navigation
     */
    public function getSidebarAreas(): array
    {
        return $this->areaRepository->sidebarNavigation();
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
} 