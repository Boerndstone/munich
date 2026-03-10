<?php

namespace App\Service;

/**
 * Parses a topo SVG string that contains an embedded image and path overlay.
 * Extracts the image URL and returns a paths-only SVG for a two-layer display:
 * background image + vector paths overlay (supports high-resolution images).
 *
 * Topo image variants (compile: -low 750q5, default 750q100, -high 750q100, @2x 1024q100).
 * srcset uses only distinct width descriptors (750w + 1024w) so the browser can choose reliably.
 * For quality-based selection (e.g. -low on slow/Save-Data) you’d need <picture> or JS.
 */
class TopoSvgParser
{
    private const VARIANT_LOW = '-low';
    private const VARIANT_HIGH = '-high';
    private const VARIANT_2X = '@2x';
    private const WIDTH_DEFAULT = 750;
    private const WIDTH_2X = 1024;

    /**
     * Returns parsed data: imageUrl, pathsSvg (SVG without the image element), viewBox (e.g. "0 0 1024 820").
     * If no image element is found, pathsSvg is the original SVG and imageUrl is null.
     *
     * @return array{imageUrl: string|null, pathsSvg: string, viewBox: string|null}
     */
    public function parse(string $svg): array
    {
        $svg = trim($svg);
        if ($svg === '') {
            return ['imageUrl' => null, 'pathsSvg' => $svg, 'viewBox' => null];
        }

        $viewBox = $this->extractViewBox($svg);
        $imageUrl = $this->extractImageUrl($svg);
        $pathsSvg = $this->removeImageElement($svg);

        return [
            'imageUrl' => $imageUrl,
            'pathsSvg' => $pathsSvg,
            'viewBox' => $viewBox,
        ];
    }

    private function extractViewBox(string $svg): ?string
    {
        if (preg_match('/viewBox\s*=\s*["\']([^"\']+)["\']/i', $svg, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extractImageUrl(string $svg): ?string
    {
        // xlink:href (legacy) or href
        if (preg_match('/<image\s[^>]*(?:xlink:)?href\s*=\s*["\']([^"\']+)["\']/i', $svg, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Removes the first <image ...> or <image ... /> element from the SVG.
     */
    private function removeImageElement(string $svg): string
    {
        // Match <image ... /> or <image ...></image>
        $pathsSvg = preg_replace('/<image\s[^>]*\/>\s*/is', '', $svg, 1);
        if ($pathsSvg !== $svg) {
            return trim($pathsSvg);
        }
        $pathsSvg = preg_replace('/<image\s[^>]*>[\s]*<\/image>\s*/is', '', $svg, 1);
        if ($pathsSvg !== $svg) {
            return trim($pathsSvg);
        }
        return $svg;
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
     * Build responsive topo image src/srcset from a single image URL.
     * Emits only distinct width descriptors (750w, 1024w) so browser selection is well-defined.
     * Expects compile variants: base.webp (750q100), base@2x.webp (1024q100).
     * Query string and fragment from the original URL are re-appended to each generated URL.
     *
     * @return array{src: string, srcset: string, sizes: string}
     */
    public function buildTopoImageSrcset(string $imageUrl): array
    {
        $parts = parse_url($imageUrl);
        $path = $parts['path'] ?? $imageUrl;
        if ($path === '') {
            return ['src' => $imageUrl, 'srcset' => '', 'sizes' => ''];
        }

        $ext = $this->getExtension($path);
        $basePath = $this->getTopoImageBasePath($path);
        if ($basePath === null || $ext === '') {
            return ['src' => $imageUrl, 'srcset' => '', 'sizes' => ''];
        }

        $append = $this->appendQueryFragment('', $parts);

        $src = $basePath . $ext . $append;

        $candidates = [
            $basePath . $ext . $append . ' ' . self::WIDTH_DEFAULT . 'w',
            $basePath . self::VARIANT_2X . $ext . $append . ' ' . self::WIDTH_2X . 'w',
        ];
        $srcset = implode(', ', $candidates);
        $sizes = '(max-width: 768px) 100vw, 750px';

        return [
            'src' => $src,
            'srcset' => $srcset,
            'sizes' => $sizes,
        ];
    }

    private function appendQueryFragment(string $path, array $parts): string
    {
        $out = $path;
        if (!empty($parts['query'])) {
            $out .= '?' . $parts['query'];
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $out .= '#' . $parts['fragment'];
        }
        return $out;
    }

    /** Returns path without extension and without any known variant suffix (query/fragment not included). */
    private function getTopoImageBasePath(string $pathOnly): ?string
    {
        $path = $pathOnly;
        $ext = $this->getExtension($path);
        if ($ext !== '') {
            $path = substr($path, 0, -\strlen($ext));
        }
        foreach ([self::VARIANT_2X, self::VARIANT_HIGH, self::VARIANT_LOW] as $suffix) {
            if (str_ends_with($path, $suffix)) {
                $path = substr($path, 0, -\strlen($suffix));
                break;
            }
        }
        return $path;
    }

    private function getExtension(string $pathOrUrl): string
    {
        $path = $pathOrUrl;
        $q = strpos($path, '?');
        $h = strpos($path, '#');
        $end = $path;
        if ($q !== false && ($h === false || $q < $h)) {
            $end = substr($path, 0, $q);
        } elseif ($h !== false) {
            $end = substr($path, 0, $h);
        }
        if (preg_match('/\.(webp|jpg|jpeg|png|avif)(?:\?|#|$)/i', $end, $m)) {
            return '.' . strtolower($m[1]);
        }
        return '';
    }
}
