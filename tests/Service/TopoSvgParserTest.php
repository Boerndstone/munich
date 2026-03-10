<?php

namespace App\Tests\Service;

use App\Service\TopoSvgParser;
use PHPUnit\Framework\TestCase;

class TopoSvgParserTest extends TestCase
{
    private TopoSvgParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TopoSvgParser();
    }

    // --- parse() ---

    public function testParseEmptySvg(): void
    {
        $result = $this->parser->parse('');
        $this->assertNull($result['imageUrl']);
        $this->assertSame('', $result['pathsSvg']);
        $this->assertNull($result['viewBox']);
    }

    public function testParseWhitespaceOnly(): void
    {
        $result = $this->parser->parse("  \n\t  ");
        $this->assertNull($result['imageUrl']);
        $this->assertSame('', $result['pathsSvg']);
        $this->assertNull($result['viewBox']);
    }

    public function testParseExtractsViewBox(): void
    {
        $svg = '<svg viewBox="0 0 1024 820" xmlns="http://www.w3.org/2000/svg"><path d="M0,0"/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertSame('0 0 1024 820', $result['viewBox']);
    }

    public function testParseMissingViewBox(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0,0"/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertNull($result['viewBox']);
    }

    public function testParseViewBoxSingleQuotes(): void
    {
        $svg = "<svg viewBox='0 0 800 600'><path d=''/></svg>";
        $result = $this->parser->parse($svg);
        $this->assertSame('0 0 800 600', $result['viewBox']);
    }

    public function testParseExtractsImageHref(): void
    {
        $svg = '<svg viewBox="0 0 1024 820"><image width="1024" height="820" href="https://example.com/topos/rock.webp" x="0" y="0"/><path d="M0,0"/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertSame('https://example.com/topos/rock.webp', $result['imageUrl']);
        $this->assertStringNotContainsString('<image', $result['pathsSvg']);
        $this->assertStringContainsString('<path d="M0,0"/>', $result['pathsSvg']);
    }

    public function testParseExtractsImageXlinkHref(): void
    {
        $svg = '<svg xmlns:xlink="http://www.w3.org/1999/xlink"><image xlink:href="/build/images/topos/suedwand.webp" width="1024" height="820"/><path d="m1,2"/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/suedwand.webp', $result['imageUrl']);
        $this->assertStringNotContainsString('<image', $result['pathsSvg']);
        $this->assertStringContainsString('<path d="m1,2"/>', $result['pathsSvg']);
    }

    public function testParseRemovesSelfClosingImage(): void
    {
        $svg = '<svg><image href="https://x.com/t.webp" x="0" y="0" width="100" height="100"/><path id="p1" d=""/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertSame('https://x.com/t.webp', $result['imageUrl']);
        $this->assertStringNotContainsString('image', $result['pathsSvg']);
        $this->assertStringContainsString('<path id="p1"', $result['pathsSvg']);
    }

    public function testParseRemovesPairedImageTag(): void
    {
        $svg = '<svg><image href="https://x.com/t.webp" x="0" y="0"></image><path d=""/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertSame('https://x.com/t.webp', $result['imageUrl']);
        $this->assertStringNotContainsString('image', $result['pathsSvg']);
        $this->assertStringContainsString('<path d=""', $result['pathsSvg']);
    }

    public function testParseNoImageLeavesSvgUnchanged(): void
    {
        $svg = '<svg viewBox="0 0 100 100"><path d="M0,0 L10,10"/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertNull($result['imageUrl']);
        $this->assertSame($svg, $result['pathsSvg']);
    }

    public function testParseImageUrlWithQueryString(): void
    {
        $svg = '<svg><image href="https://example.com/topo.webp?v=1"/><path d=""/></svg>';
        $result = $this->parser->parse($svg);
        $this->assertSame('https://example.com/topo.webp?v=1', $result['imageUrl']);
    }

    // --- viewBoxToAspectRatio() ---

    public function testViewBoxToAspectRatioValid(): void
    {
        $this->assertSame('1024 / 820', $this->parser->viewBoxToAspectRatio('0 0 1024 820'));
        $this->assertSame('800 / 600', $this->parser->viewBoxToAspectRatio('0 0 800 600'));
    }

    public function testViewBoxToAspectRatioNull(): void
    {
        $this->assertNull($this->parser->viewBoxToAspectRatio(null));
    }

    public function testViewBoxToAspectRatioEmpty(): void
    {
        $this->assertNull($this->parser->viewBoxToAspectRatio(''));
    }

    public function testViewBoxToAspectRatioInvalidTooFewParts(): void
    {
        $this->assertNull($this->parser->viewBoxToAspectRatio('0 0'));
    }

    public function testViewBoxToAspectRatioZeroHeightReturnsNull(): void
    {
        $this->assertNull($this->parser->viewBoxToAspectRatio('0 0 100 0'));
    }

    // --- buildTopoImageSrcset() ---

    public function testBuildTopoImageSrcsetSimplePath(): void
    {
        $url = 'https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand.webp', $result['src']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand.webp 750w', $result['srcset']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand@2x.webp 1024w', $result['srcset']);
        $this->assertSame('(max-width: 768px) 100vw, 750px', $result['sizes']);
    }

    public function testBuildTopoImageSrcsetWithQueryString(): void
    {
        $url = '/build/images/topos/rock.webp?v=1';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/rock.webp?v=1', $result['src']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/rock.webp?v=1 750w', $result['srcset']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/rock@2x.webp?v=1 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetWithFragment(): void
    {
        $url = 'https://example.com/topos/wand.webp#section';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://example.com/topos/wand.webp#section', $result['src']);
        $this->assertStringContainsString('wand.webp#section 750w', $result['srcset']);
        $this->assertStringContainsString('wand@2x.webp#section 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetWithQueryAndFragment(): void
    {
        $url = 'https://example.com/topo.webp?v=2#anchor';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://example.com/topo.webp?v=2#anchor', $result['src']);
        $this->assertStringContainsString('topo.webp?v=2#anchor 750w', $result['srcset']);
        $this->assertStringContainsString('topo@2x.webp?v=2#anchor 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetUrlAlreadyHasVariantSuffix(): void
    {
        $url = 'https://cdn.example/topos/face@2x.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://cdn.example/topos/face.webp', $result['src']);
        $this->assertStringContainsString('face.webp ', $result['srcset']);
        $this->assertStringContainsString('face@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetNoExtensionReturnsOriginalUrlEmptySrcset(): void
    {
        $url = 'https://example.com/noext';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame($url, $result['src']);
        $this->assertSame('', $result['srcset']);
        $this->assertSame('', $result['sizes']);
    }

    public function testBuildTopoImageSrcsetEmptyPathReturnsOriginalUrlEmptySrcset(): void
    {
        $url = 'https://example.com';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame($url, $result['src']);
        $this->assertSame('', $result['srcset']);
        $this->assertSame('', $result['sizes']);
    }

    public function testBuildTopoImageSrcsetPreservesJpgExtension(): void
    {
        $url = '/topos/photo.jpg';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/topos/photo.jpg', $result['src']);
        $this->assertStringContainsString('https://www.munichclimbs.de/topos/photo.jpg 750w', $result['srcset']);
        $this->assertStringContainsString('https://www.munichclimbs.de/topos/photo@2x.jpg 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetPreservesPngExtension(): void
    {
        $url = '/topos/diagram.png?v=1';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/topos/diagram.png?v=1', $result['src']);
        $this->assertStringContainsString('https://www.munichclimbs.de/topos/diagram.png?v=1', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetLowSuffixStrippedFromBase(): void
    {
        $url = '/build/topos/name-low.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/topos/name.webp', $result['src']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/topos/name@2x.webp', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetHighSuffixStrippedFromBase(): void
    {
        $url = '/topos/name-high.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/topos/name.webp', $result['src']);
    }
}
