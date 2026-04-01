<?php

namespace App\Service;

/**
 * Renders topo path config arrays (d, color, dot, dashed) into SVG markup
 * matching the admin Topo Path Helper output: border path, main path, number circle, text, optional end-dot.
 */
class TopoPathRendererService
{
    private const DEFS_MARKER = '<defs><marker id="dot" markerWidth="10" markerHeight="10" refX="5" refY="5" markerUnits="strokeWidth"><circle cx="5" cy="5" r="3" fill="white"/></marker></defs>';

    /**
     * Renders path config array to SVG inner content (no outer <svg>).
     * Each path config: d (string), color (string, optional), dot (bool, optional), dashed (bool, optional).
     * Returns defs + path/circle/text elements. Include in <svg viewBox="..."> and ensure defs only once.
     *
     * @param array<int, array{d?: string, color?: string, dot?: bool, dashed?: bool}> $paths
     */
    public function renderPathsToSvg(array $paths): string
    {
        if (empty($paths)) {
            return '';
        }
        $parts = [self::DEFS_MARKER];
        $needsDefs = false;
        foreach ($paths as $i => $path) {
            $path = is_array($path) ? $path : [];
            $d = $path['d'] ?? '';
            if ($d === '') {
                continue;
            }
            $id = $i + 1;
            $color = $path['color'] ?? '#E42522';
            $dashed = !empty($path['dashed']);
            $dot = !empty($path['dot']);
            if ($dot) {
                $needsDefs = true;
            }

            if (!preg_match('/m([\d.]+),([\d.]+)/i', $d, $m)) {
                continue;
            }
            $startX = $m[1];
            $startY = $m[2];
            $startYMinusOne = (float) $startY - 1;

            $borderStroke = $dashed ? '#ffffff' : '#000000';
            $borderClass = $dashed ? 'route-path-border dashed' : 'route-path-border';
            $parts[] = sprintf(
                '<path id="border_%d" d="%s" stroke-width="4" stroke="%s" fill="none" class="%s" pointer-events="none"></path>',
                $id,
                $this->escapeAttr($d),
                $borderStroke,
                $borderClass
            );
            // No path marker when dot: we draw the explicit end-dot circle instead (avoids white ring)
            $markerAttr = '';
            $parts[] = sprintf(
                '<path id="svg_%d" d="%s" stroke-width="3" stroke="%s"%s fill="none" class="stroke-behavior" pointer-events="none"></path>',
                $id,
                $this->escapeAttr($d),
                $this->escapeAttr($color),
                $markerAttr
            );
            // Wider invisible stroke for clicks along the route (keeps visible line thin)
            $parts[] = sprintf(
                '<path d="%s" stroke="rgba(0,0,0,0)" stroke-width="14" fill="none" class="route-path-hit tooltip-trigger" data-path-id="%d"></path>',
                $this->escapeAttr($d),
                $id
            );
            $circleStroke = $dashed ? '#ffffff' : '#000';
            $circleClass = $dashed ? 'number-circle' : 'number-circle tooltip-trigger';
            $parts[] = sprintf(
                '<circle cx="%s" cy="%s" r="18" fill="%s" stroke="%s" stroke-width="1" class="%s" data-path-id="%d"></circle>',
                $startX,
                $startYMinusOne,
                $this->escapeAttr($color),
                $circleStroke,
                $circleClass,
                $id
            );
            $parts[] = sprintf(
                '<text x="%s" y="%s" fill="#fff" font-size="16px" class="number-text tooltip-trigger" data-path-id="%d">%d</text>',
                $startX,
                $startY,
                $id,
                $id
            );
            if ($dot) {
                $end = $this->calculateEndpoint($d);
                $parts[] = sprintf('<circle cx="%s" cy="%s" r="7" fill="#fff" class="end-dot"></circle>', $end[0], $end[1]);
            }
        }
        if (!$needsDefs) {
            $parts[0] = '';
        }
        return implode('', $parts);
    }

    /**
     * Returns [x, y] of the path end point by parsing the 'd' attribute.
     */
    public function calculateEndpoint(string $d): array
    {
        $commands = preg_split('/(?=[a-zA-Z])/', trim($d), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $currentX = 0.0;
        $currentY = 0.0;
        $endX = 0.0;
        $endY = 0.0;
        foreach ($commands as $cmd) {
            $type = $cmd[0];
            $rest = substr($cmd, 1);
            $values = preg_split('/[\s,]+/', trim($rest), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $values = array_map('floatval', $values);
            switch ($type) {
                case 'M':
                case 'm':
                    $currentX = $values[0] ?? $currentX;
                    $currentY = $values[1] ?? $currentY;
                    break;
                case 'L':
                case 'l':
                    if ($type === 'L') {
                        $currentX = $values[0] ?? $currentX;
                        $currentY = $values[1] ?? $currentY;
                    } else {
                        $currentX += $values[0] ?? 0;
                        $currentY += $values[1] ?? 0;
                    }
                    break;
                case 'C':
                case 'c':
                    if ($type === 'C') {
                        $currentX = $values[4] ?? $currentX;
                        $currentY = $values[5] ?? $currentY;
                    } else {
                        $currentX += $values[4] ?? 0;
                        $currentY += $values[5] ?? 0;
                    }
                    break;
                case 'S':
                case 's':
                    if ($type === 'S') {
                        $currentX = $values[2] ?? $currentX;
                        $currentY = $values[3] ?? $currentY;
                    } else {
                        $currentX += $values[2] ?? 0;
                        $currentY += $values[3] ?? 0;
                    }
                    break;
                case 'Q':
                case 'q':
                    if ($type === 'Q') {
                        $currentX = $values[2] ?? $currentX;
                        $currentY = $values[3] ?? $currentY;
                    } else {
                        $currentX += $values[2] ?? 0;
                        $currentY += $values[3] ?? 0;
                    }
                    break;
                case 'T':
                case 't':
                    if ($type === 'T') {
                        $currentX = $values[0] ?? $currentX;
                        $currentY = $values[1] ?? $currentY;
                    } else {
                        $currentX += $values[0] ?? 0;
                        $currentY += $values[1] ?? 0;
                    }
                    break;
                case 'A':
                case 'a':
                    if ($type === 'A') {
                        $currentX = $values[5] ?? $currentX;
                        $currentY = $values[6] ?? $currentY;
                    } else {
                        $currentX += $values[5] ?? 0;
                        $currentY += $values[6] ?? 0;
                    }
                    break;
                case 'H':
                case 'h':
                    if ($type === 'H') {
                        $currentX = $values[0] ?? $currentX;
                    } else {
                        $currentX += $values[0] ?? 0;
                    }
                    break;
                case 'V':
                case 'v':
                    if ($type === 'V') {
                        $currentY = $values[0] ?? $currentY;
                    } else {
                        $currentY += $values[0] ?? 0;
                    }
                    break;
            }
            $endX = $currentX;
            $endY = $currentY;
        }
        return [$endX, $endY];
    }

    /**
     * Resolves topo paths for overlay: use pre-rendered pathCollection if it looks like SVG,
     * otherwise decode pathCollection or path as JSON array and render.
     * Returns HTML-safe string (defs + path/circle/text) to put inside <svg>.
     */
    public function resolvePathsOverlay(?string $pathCollection, $pathData): string
    {
        $pathCollection = $pathCollection !== null ? trim($pathCollection) : '';
        // Do not treat JavaScript source (e.g. from Step 5 textarea) as raw SVG
        $looksLikeJsSource = str_contains($pathCollection, "' + ") || str_contains($pathCollection, 'escapeHtml(')
            || str_contains($pathCollection, "' + w + '") || str_contains($pathCollection, "' + h + '");
        if ($pathCollection !== '' && !$looksLikeJsSource && (str_contains($pathCollection, '<path') || str_contains($pathCollection, '<circle'))) {
            $content = $this->stripOuterSvgIfPresent($pathCollection);
            $content = $this->sanitizeSvgContent($content);
            return $this->ensureDefsInContent($content);
        }
        $paths = $this->decodePathData($pathCollection, $pathData);
        if ($paths === null || empty($paths)) {
            if ($pathCollection === '' || $looksLikeJsSource) {
                return '';
            }
            $content = $this->stripOuterSvgIfPresent($pathCollection);
            $content = $this->sanitizeSvgContent($content);
            return $this->ensureDefsInContent($content);
        }
        return $this->renderPathsToSvg($paths);
    }

    /**
     * Sanitize SVG inner content by allowing only a safe subset of elements and attributes.
     * This is used for pre-rendered path collections that are later rendered with |raw.
     */
    private function sanitizeSvgContent(string $content): string
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return $trimmed;
        }

        $wrapper = '<svg xmlns="http://www.w3.org/2000/svg">' . $trimmed . '</svg>';

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $prevUseErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($wrapper, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        if (!$loaded || $dom->documentElement === null) {
            // Fallback: very simple sanitization if XML parsing fails.
            $sanitized = preg_replace('#<\s*script\b[^>]*>.*?<\s*/\s*script\s*>#is', '', $trimmed);
            $sanitized = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $sanitized);
            return is_string($sanitized) ? $sanitized : '';
        }

        $allowedElements = [
            'svg'    => true,
            'g'      => true,
            'path'   => true,
            'circle' => true,
            'text'   => true,
            'defs'   => true,
            'marker' => true,
            'title'  => true,
            'desc'   => true,
        ];

        $allowedAttributes = [
            'id'            => true,
            'class'         => true,
            'pointer-events'=> true,
            'd'             => true,
            'fill'          => true,
            'stroke'        => true,
            'stroke-width'  => true,
            'stroke-linecap'=> true,
            'stroke-linejoin'=> true,
            'stroke-dasharray'=> true,
            'cx'            => true,
            'cy'            => true,
            'r'             => true,
            'x'             => true,
            'y'             => true,
            'dx'            => true,
            'dy'            => true,
            'text-anchor'   => true,
            'font-size'     => true,
            'marker-start'  => true,
            'marker-mid'    => true,
            'marker-end'    => true,
            'viewBox'       => true,
            'xmlns'         => true,
            'markerWidth'   => true,
            'markerHeight'  => true,
            'refX'          => true,
            'refY'          => true,
            'markerUnits'   => true,
        ];

        $xpath = new \DOMXPath($dom);
        /** @var \DOMElement $node */
        foreach ($xpath->query('//*') as $node) {
            $tagName = $node->tagName;
            if (!isset($allowedElements[$tagName])) {
                if ($node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
                continue;
            }

            if (!$node->hasAttributes()) {
                continue;
            }

            // Collect attributes to remove (cannot modify NamedNodeMap while iterating directly).
            $attrsToRemove = [];
            foreach (iterator_to_array($node->attributes) as $attr) {
                $name = $attr->nodeName;
                $lname = strtolower($name);
                if (str_starts_with($lname, 'on')) {
                    $attrsToRemove[] = $name;
                    continue;
                }
                if ($lname === 'href' || $lname === 'xlink:href') {
                    $attrsToRemove[] = $name;
                    continue;
                }
                if (!isset($allowedAttributes[$name])) {
                    $attrsToRemove[] = $name;
                }
            }

            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        $root = $dom->documentElement;
        if ($root === null) {
            return '';
        }

        $inner = '';
        foreach ($root->childNodes as $child) {
            $inner .= $dom->saveXML($child);
        }

        return $inner;
    }
    /** If content is wrapped in <svg>...</svg>, return only the inner content (and drop <image> if present). */
    private function stripOuterSvgIfPresent(string $content): string
    {
        if (preg_match('/^\s*<svg[^>]*>(.*)<\/svg>\s*$/is', $content, $m)) {
            $inner = trim($m[1]);
            $inner = preg_replace('/<image\b[^>]*\/?\s*>(?:.*?<\/image\s*>|)/is', '', $inner);
            return trim($inner);
        }
        return $content;
    }

    /**
     * Decodes path data for a topo (path_collection and/or path) into an array of path configs.
     * Use when you need to pass path configs to the admin (e.g. edit-paths page).
     *
     * @return array<int, array{d?: string, color?: string, dot?: bool, dashed?: bool}>|null
     */
    public function decodePathsForTopo(?string $pathCollection, $pathData): ?array
    {
        return $this->decodePathData($pathCollection ?? '', $pathData);
    }

    private function decodePathData(string $pathCollection, $pathData): ?array
    {
        if (is_array($pathData) && !empty($pathData)) {
            return $pathData;
        }
        if (is_string($pathData)) {
            $decoded = json_decode($pathData, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            $parsed = $this->parsePhpPathArrayLiteral($pathData);
            if ($parsed !== null) {
                return $parsed;
            }
        }
        if ($pathCollection !== '') {
            $decoded = json_decode($pathCollection, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            $parsed = $this->parsePhpPathArrayLiteral($pathCollection);
            if ($parsed !== null) {
                return $parsed;
            }
        }
        return null;
    }

    /**
     * Parses PHP array literal string like ["'d' => 'm1,2c3,-4', 'color' => '#a16207', 'dot' => true], ..."]
     * into array of path configs. Splits by "], [" so each segment is one array, then parses each.
     */
    private function parsePhpPathArrayLiteral(string $str): ?array
    {
        $str = trim($str);
        if ($str === '' || !str_contains($str, "'d'")) {
            return null;
        }
        // Split by array boundary "], " or "],\n" or "], [" so we get one segment per path array.
        // This avoids mis-parsing when 'd' values contain commas.
        $segments = preg_split('~\]\s*,\s*\[~', $str);
        if ($segments === false || $segments === []) {
            return null;
        }
        $paths = [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            // Ensure segment looks like a single array: [ ... ]
            if (!str_contains($segment, "'d'")) {
                continue;
            }
            if ($segment[0] !== '[') {
                $segment = '[' . $segment;
            }
            if (substr($segment, -1) !== ']') {
                $segment = $segment . ']';
            }
            $item = $this->extractOnePhpPathArray($segment, 0);
            if ($item === null) {
                continue;
            }
            unset($item['_next']);
            $paths[] = $item;
        }
        return $paths !== [] ? $paths : null;
    }

    /**
     * Extracts one path config from PHP array literal at $offset. Returns array with d, color, dot, dashed and _next offset, or null.
     */
    private function extractOnePhpPathArray(string $str, int $offset): ?array
    {
        $offset += strspn($str, " \t\n\r[,", $offset);
        if (!preg_match("/'d'\s*=>\s*'/", $str, $m, 0, $offset)) {
            return null;
        }
        $start = $offset + strlen($m[0]);
        $d = $this->extractSingleQuotedString($str, $start);
        if ($d === null) {
            return null;
        }
        $result = ['d' => $d['value']];
        $after = $d['end'];
        $len = strlen($str);
        while ($after < $len) {
            $after += strspn($str, " \t\n\r,", $after);
            if ($after >= $len) {
                break;
            }
            // If we're on a single quote that ends a value (', ' or '] or end), skip it to reach next key
            if ($str[$after] === "'" && ($after + 1 >= $len || $str[$after + 1] === ',' || $str[$after + 1] === ' ' || $str[$after + 1] === ']')) {
                $after += 1;
            }
            if (preg_match("/'color'\s*=>\s*'/", $str, $cm, 0, $after)) {
                $cs = $this->extractSingleQuotedString($str, $after + strlen($cm[0]));
                if ($cs !== null) {
                    $result['color'] = $cs['value'];
                    $after = $cs['end'];
                    continue;
                }
            }
            if (preg_match("/'dot'\s*=>\s*(true|false)/", $str, $dm, 0, $after)) {
                $result['dot'] = $dm[1] === 'true';
                $after += strlen($dm[0]);
                continue;
            }
            if (preg_match("/'dashed'\s*=>\s*(true|false)/", $str, $dm, 0, $after)) {
                $result['dashed'] = $dm[1] === 'true';
                $after += strlen($dm[0]);
                continue;
            }
            // Only skip whitespace before checking for end of array (do not skip comma, or we jump past our ])
            $after += strspn($str, " \t\n\r", $after);
            if ($after >= $len) {
                $result['_next'] = $len;
                return $result;
            }
            if ($str[$after] === ']') {
                $result['_next'] = $after + 1;
                return $result;
            }
            if ($str[$after] === ',') {
                $commaPos = $after;
                $after += 1 + strspn($str, " \t\n\r", $after + 1);
                if ($after < $len && $str[$after] === '[') {
                    // Comma was the one after our array's ]; next array starts at '['
                    $result['_next'] = $commaPos;
                    return $result;
                }
                continue;
            }
            $nextBracket = strpos($str, ']', $after);
            if ($nextBracket !== false) {
                $result['_next'] = $nextBracket + 1;
                return $result;
            }
            break;
        }
        $result['_next'] = $len;
        return $result;
    }

    /** Returns ['value' => unescaped string, 'end' => offset after closing quote] or null. */
    private function extractSingleQuotedString(string $str, int $start): ?array
    {
        $len = strlen($str);
        $value = '';
        $i = $start;
        while ($i < $len) {
            $c = $str[$i];
            if ($c === '\\' && $i + 1 < $len) {
                $value .= $str[$i + 1];
                $i += 2;
                continue;
            }
            if ($c === "'") {
                return ['value' => $value, 'end' => $i + 1];
            }
            $value .= $c;
            $i++;
        }
        return null;
    }

    private function ensureDefsInContent(string $content): string
    {
        if (str_contains($content, 'url(#dot)') && !str_contains($content, 'id="dot"')) {
            return self::DEFS_MARKER . $content;
        }
        return $content;
    }

    private function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
