<?php

namespace App\Service;

/**
 * Helper for topo image display: builds responsive srcset from a topo image name
 * and converts viewBox to CSS aspect-ratio.
 *
 * Topo images always live at: https://www.munichclimbs.de/build/images/topos/{name}.webp
 * with @2x variant: {name}@2x.webp. srcset uses 750w + 1024w.
 */
class TopoSvgParser
{
    private const VARIANT_2X = '@2x';
    private const WIDTH_DEFAULT = 750;
    private const WIDTH_2X = 1024;

    private const TOPO_IMAGE_PATH = '/build/images/topos/';
    private const DEFAULT_BASE_URL = 'https://www.munichclimbs.de';

    public function __construct(
        private readonly string $baseUrl = self::DEFAULT_BASE_URL
    ) {
    }

    /**
     * Returns aspect-ratio value for CSS from viewBox (e.g. "0 0 1024 820" -> "1024 / 820").
     */
    public function viewBoxToAspectRatio(?string $viewBox): ?string
    {
        if ($viewBox === null || $viewBox === '') {
            return null;
        }
        $parts = preg_split('/\s+/', trim($viewBox), 4);
        if (count($parts) >= 4 && is_numeric($parts[2]) && is_numeric($parts[3]) && (float) $parts[3] > 0) {
            return $parts[2] . ' / ' . $parts[3];
        }
        return null;
    }

    /**
     * Build responsive topo image src/srcset from the topo image name (or full URL).
     * Always constructs: baseUrl + /build/images/topos/ + name + .webp and name + @2x.webp.
     *
     * @param string $imageName Topo image name (e.g. "burgsteinNebenmassiv" or "burgsteinNebenmassiv.webp")
     *                          or full URL; the basename without extension and without @2x is used as name.
     * @return array{src: string, srcset: string, sizes: string}
     */
    public function buildTopoImageSrcset(string $imageName): array
    {
        $name = $this->extractTopoImageBasename($imageName);
        if ($name === '') {
            $src = $this->ensureAbsoluteUrl($imageName);
            return ['src' => $src, 'srcset' => '', 'sizes' => ''];
        }

        $base = rtrim($this->baseUrl, '/') . self::TOPO_IMAGE_PATH . $name;
        $src = $base . '.webp';
        $url2x = $base . self::VARIANT_2X . '.webp';
        $srcset = $src . ' ' . self::WIDTH_DEFAULT . 'w, ' . $url2x . ' ' . self::WIDTH_2X . 'w';
        $sizes = '(max-width: 768px) 100vw, 750px';

        return [
            'src' => $src,
            'srcset' => $srcset,
            'sizes' => $sizes,
        ];
    }

    /**
     * Extracts the topo image basename (no path, no extension, no @2x/-low/-high).
     */
    private function extractTopoImageBasename(string $imageName): string
    {
        $imageName = trim($imageName);
        if ($imageName === '') {
            return '';
        }
        $path = $imageName;
        if (str_contains($imageName, '://')) {
            $parts = parse_url($imageName);
            $path = $parts['path'] ?? '';
            if ($path === '' || $path === '/') {
                return '';
            }
        }
        $path = ltrim($path, '/');
        $q = strpos($path, '?');
        $h = strpos($path, '#');
        if ($q !== false || $h !== false) {
            $end = $q !== false && ($h === false || $q < $h) ? $q : $h;
            $path = substr($path, 0, $end);
        }
        $base = $path;
        if (str_contains($base, '/')) {
            $base = substr($base, strrpos($base, '/') + 1);
        }
        if (preg_match('/\.(webp|jpg|jpeg|png|avif)(?:\?|#|$)/i', $base, $m)) {
            $base = substr($base, 0, -\strlen($m[0]));
        }
        foreach ([self::VARIANT_2X, '-high', '-low'] as $suffix) {
            if (str_ends_with($base, $suffix)) {
                $base = substr($base, 0, -\strlen($suffix));
                break;
            }
        }
        if ($base === '' || str_contains($base, '://')) {
            return '';
        }
        return $base;
    }

    private function ensureAbsoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $this->baseUrl;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        $base = rtrim($this->baseUrl, '/');
        return $base . '/' . ltrim($url, '/');
    }
}
