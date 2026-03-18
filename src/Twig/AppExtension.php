<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Service\AreasService;
use App\Service\ImageSeoService;
use App\Service\TopoSvgParser;
use App\Service\TopoPathRendererService;
use App\Util\SlugUtil;

class AppExtension extends AbstractExtension
{

    private AreasService $areasService;
    private ImageSeoService $imageSeoService;
    private TopoSvgParser $topoSvgParser;
    private TopoPathRendererService $topoPathRenderer;

    public function __construct(AreasService $areasService, ImageSeoService $imageSeoService, TopoSvgParser $topoSvgParser, TopoPathRendererService $topoPathRenderer)
    {
        $this->areasService = $areasService;
        $this->imageSeoService = $imageSeoService;
        $this->topoSvgParser = $topoSvgParser;
        $this->topoPathRenderer = $topoPathRenderer;
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
            new TwigFunction('topo_paths_overlay', [$this, 'topoPathsOverlay']),
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
            new TwigFilter('viewbox_to_aspect_ratio', [$this, 'viewBoxToAspectRatio']),
            new TwigFilter('topo_image_srcset', [$this, 'topoImageSrcset']),
        ];
    }

    /**
     * Normalize for IDs/display: strip punctuation, expand umlauts (via SlugUtil), underscore → space.
     */
    public function customReplaceFilter($value)
    {
        $replacements = [' ', '!', '&', '.', ','];
        $value = str_replace($replacements, '', $value);
        $value = SlugUtil::umlautsToAscii($value);
        $value = str_replace('_', ' ', $value);
        return $value;
    }

    /**
     * Converts SVG viewBox (e.g. "0 0 1024 820") to CSS aspect-ratio ("1024 / 820").
     */
    public function viewBoxToAspectRatio(?string $viewBox): ?string
    {
        return $this->topoSvgParser->viewBoxToAspectRatio($viewBox);
    }

    /**
     * Returns { src, srcset, sizes } for a topo image URL for responsive display.
     *
     * @return array{src: string, srcset: string, sizes: string}
     */
    public function topoImageSrcset(?string $imageUrl): array
    {
        if ($imageUrl === null || $imageUrl === '') {
            return ['src' => '', 'srcset' => '', 'sizes' => ''];
        }
        return $this->topoSvgParser->buildTopoImageSrcset($imageUrl);
    }

    /**
     * Resolves topo paths for overlay: uses pre-rendered pathCollection if it's SVG,
     * otherwise renders from path config (pathCollection or path as JSON array).
     * Returns safe HTML to put inside <svg> (defs + path/circle/text).
     */
    public function topoPathsOverlay(?string $pathCollection, $pathData = null): string
    {
        return $this->topoPathRenderer->resolvePathsOverlay($pathCollection ?? '', $pathData);
    }
}
