<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Service\AreasService;
use App\Service\ImageSeoService;
use App\Service\TopoSvgParser;

class AppExtension extends AbstractExtension
{

    private AreasService $areasService;
    private ImageSeoService $imageSeoService;
    private TopoSvgParser $topoSvgParser;

    public function __construct(AreasService $areasService, ImageSeoService $imageSeoService, TopoSvgParser $topoSvgParser)
    {
        $this->areasService = $areasService;
        $this->imageSeoService = $imageSeoService;
        $this->topoSvgParser = $topoSvgParser;
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
            new TwigFilter('parse_topo_svg', [$this, 'parseTopoSvg']),
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

    /**
     * Parses a topo SVG string into imageUrl, pathsSvg and viewBox for two-layer rendering (background image + paths overlay).
     * When imageUrl is present, also adds src, srcset and sizes for responsive topo images (-low, default, -high, @2x).
     *
     * @return array{imageUrl: string|null, pathsSvg: string, viewBox: string|null, aspectRatio: string|null, src: string|null, srcset: string, sizes: string}
     */
    public function parseTopoSvg(?string $svg): array
    {
        if ($svg === null || $svg === '') {
            return ['imageUrl' => null, 'pathsSvg' => '', 'viewBox' => null, 'aspectRatio' => null, 'src' => null, 'srcset' => '', 'sizes' => ''];
        }
        $parsed = $this->topoSvgParser->parse($svg);
        $parsed['aspectRatio'] = $this->topoSvgParser->viewBoxToAspectRatio($parsed['viewBox']);
        if (!empty($parsed['imageUrl'])) {
            $srcsetData = $this->topoSvgParser->buildTopoImageSrcset($parsed['imageUrl']);
            $parsed['src'] = $srcsetData['src'];
            $parsed['srcset'] = $srcsetData['srcset'];
            $parsed['sizes'] = $srcsetData['sizes'];
        } else {
            $parsed['src'] = null;
            $parsed['srcset'] = '';
            $parsed['sizes'] = '';
        }
        return $parsed;
    }
}
