<?php

namespace App\Service;

use App\Repository\AreaRepository;

class FooterAreas
{
    private $areaRepository;

    public function __construct(AreaRepository $areaRepository)
    {
        $this->areaRepository = $areaRepository;
    }

    public function getFooterAreas(): array
    {
        // return ['areas' => $areas];
        return $this->areaRepository->getAreasFooter();
    }
}
