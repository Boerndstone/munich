<?php

namespace App\Twig;

use App\I18n\AlternateLocaleUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    public function __construct(
        AreasService $areasService,
        ImageSeoService $imageSeoService,
        TopoSvgParser $topoSvgParser,
        TopoPathRendererService $topoPathRenderer,
        private readonly AlternateLocaleUrlGenerator $alternateLocaleUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
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
            new TwigFunction('getMainMapRocks', [$this, 'getMainMapRocks']),
            new TwigFunction('getSidebarAreas', [$this, 'getSidebarAreas']),
            new TwigFunction('getSocialMediaImageUrl', [$this, 'getSocialMediaImageUrl']),
            new TwigFunction('getImageAltText', [$this, 'getImageAltText']),
            new TwigFunction('isImageAccessible', [$this, 'isImageAccessible']),
            new TwigFunction('topo_paths_overlay', [$this, 'topoPathsOverlay']),
            new TwigFunction('alternate_locale_path', [$this, 'alternateLocalePath']),
            new TwigFunction('index_path', [$this, 'indexPath']),
            new TwigFunction('area_path', [$this, 'areaPath']),
            new TwigFunction('rock_path', [$this, 'rockPath']),
        ];
    }

    public function alternateLocalePath(string $targetLocale): ?string
    {
        return $this->alternateLocaleUrlGenerator->generateForLocale($targetLocale);
    }

    public function indexPath(): string
    {
        $request = $this->requestStack->getMainRequest();
        $locale = ($request && str_starts_with($request->getPathInfo(), '/admin'))
            ? 'de'
            : ($request?->getLocale() ?? 'de');

        return $this->urlGenerator->generate('en' === $locale ? 'index_en' : 'index');
    }

    public function areaPath(string $slug): string
    {
        $request = $this->requestStack->getMainRequest();
        $locale = ($request && str_starts_with($request->getPathInfo(), '/admin'))
            ? 'de'
            : ($request?->getLocale() ?? 'de');

        return $this->urlGenerator->generate('en' === $locale ? 'show_rocks_en' : 'show_rocks', ['slug' => $slug]);
    }

    public function rockPath(string $areaSlug, string $slug): string
    {
        $request = $this->requestStack->getMainRequest();
        $locale = ($request && str_starts_with($request->getPathInfo(), '/admin'))
            ? 'de'
            : ($request?->getLocale() ?? 'de');

        return $this->urlGenerator->generate('en' === $locale ? 'show_rock_en' : 'show_rock', [
            'areaSlug' => $areaSlug,
            'slug' => $slug,
        ]);
    }

    public function getAreas(): array
    {
        return $this->areasService->getFooterAreas();
    }

    public function getAreasInformation(): array
    {
        return $this->areasService->getAreasInformation();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMainMapRocks(): array
    {
        return $this->areasService->getMainMapRocks();
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
     * Normalize for IDs/display: strip spaces and selected punctuation, expand umlauts (via SlugUtil),
     * then convert underscores to spaces.
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
