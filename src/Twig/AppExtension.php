<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Service\AreasService;
use App\Service\ImageSeoService;

class AppExtension extends AbstractExtension
{

    private AreasService $areasService;
    private ImageSeoService $imageSeoService;

    public function __construct(AreasService $areasService, ImageSeoService $imageSeoService)
    {
        $this->areasService = $areasService;
        $this->imageSeoService = $imageSeoService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getAreas', [$this, 'getAreas']),
            new TwigFunction('getAreasInformation', [$this, 'getAreasInformation']),
            new TwigFunction('getSidebarAreas', [$this, 'getSidebarAreas']),
            new TwigFunction('getSocialMediaImageUrl', [$this, 'getSocialMediaImageUrl']),
            new TwigFunction('getImageAltText', [$this, 'getImageAltText']),
            new TwigFunction('isImageAccessible', [$this, 'isImageAccessible']),
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

    public function getSocialMediaImageUrl(?string $imageName, string $type = 'rock'): ?string
    {
        return $this->imageSeoService->getSocialMediaImageUrl($imageName, $type);
    }

    public function getImageAltText(string $rockName, string $areaName, string $type = 'rock'): string
    {
        return $this->imageSeoService->generateImageAltText($rockName, $areaName, $type);
    }

    public function isImageAccessible(?string $imageName, string $type = 'rock'): bool
    {
        return $this->imageSeoService->isImageAccessible($imageName, $type);
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
