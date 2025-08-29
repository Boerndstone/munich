<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Service\AreasService;

class AppExtension extends AbstractExtension
{

    private AreasService $areasService;

    public function __construct(AreasService $areasService)
    {
        $this->areasService = $areasService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getAreas', [$this, 'getAreas']),
            new TwigFunction('getAreasInformation', [$this, 'getAreasInformation']),
            new TwigFunction('getSidebarAreas', [$this, 'getSidebarAreas']),
        ];
    }

    public function getAreas(): array
    {
        return $this->areasService->getFooterAreas();
    }

    public function getAreasInformation(): array
    {
        return $this->areasService->getAreasInformation();
    }

    public function getSidebarAreas(): array
    {
        return $this->areasService->getSidebarAreas();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('custom_replace', [$this, 'customReplaceFilter']),
        ];
    }

    public function customReplaceFilter($value)
    {
        // Your custom replacement logic here
        $replacements = [' ', '!', '&', '.', ','];
        $value = str_replace($replacements, '', $value);

        // Additional replacement for 'ö'
        $value = str_replace(['ö', 'ä', 'ü', '_', 'ß'], ['oe', 'ae', 'ue', ' ', 'ss'], $value);

        return $value;
    }
}
