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
    // Always builds: https://www.munichclimbs.de/build/images/topos/{name}.webp and {name}@2x.webp

    public function testBuildTopoImageSrcsetSimplePath(): void
    {
        $url = 'https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand.webp', $result['src']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand.webp 750w', $result['srcset']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/burgsteinSuedwand@2x.webp 1024w', $result['srcset']);
        $this->assertSame('(max-width: 768px) 100vw, 750px', $result['sizes']);
    }

    public function testBuildTopoImageSrcsetWithJustName(): void
    {
        $result = $this->parser->buildTopoImageSrcset('burgsteinNebenmassiv.webp');
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/burgsteinNebenmassiv.webp', $result['src']);
        $this->assertStringContainsString('burgsteinNebenmassiv.webp 750w', $result['srcset']);
        $this->assertStringContainsString('burgsteinNebenmassiv@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetWithRelativePath(): void
    {
        $url = '/build/images/topos/rock.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/rock.webp', $result['src']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/rock.webp 750w', $result['srcset']);
        $this->assertStringContainsString('https://www.munichclimbs.de/build/images/topos/rock@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetWithQueryStringStripped(): void
    {
        $url = '/build/images/topos/rock.webp?v=1';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/rock.webp', $result['src']);
        $this->assertStringContainsString('rock.webp 750w', $result['srcset']);
        $this->assertStringContainsString('rock@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetExternalUrlExtractsBasename(): void
    {
        $url = 'https://example.com/topos/wand.webp#section';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/wand.webp', $result['src']);
        $this->assertStringContainsString('wand.webp 750w', $result['srcset']);
        $this->assertStringContainsString('wand@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetUrlAlreadyHasVariantSuffix(): void
    {
        $url = 'https://cdn.example/topos/face@2x.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/face.webp', $result['src']);
        $this->assertStringContainsString('face.webp 750w', $result['srcset']);
        $this->assertStringContainsString('face@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetNoExtensionUsesNameAsIs(): void
    {
        $url = 'https://example.com/topos/noext';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/noext.webp', $result['src']);
        $this->assertStringContainsString('noext.webp 750w', $result['srcset']);
        $this->assertStringContainsString('noext@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetBareHostReturnsEmptySrcset(): void
    {
        $url = 'https://example.com';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame($url, $result['src']);
        $this->assertSame('', $result['srcset']);
        $this->assertSame('', $result['sizes']);
    }

    public function testBuildTopoImageSrcsetAlwaysWebp(): void
    {
        $url = '/topos/photo.jpg';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/photo.webp', $result['src']);
        $this->assertStringContainsString('photo.webp 750w', $result['srcset']);
        $this->assertStringContainsString('photo@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetLowSuffixStripped(): void
    {
        $url = '/build/topos/name-low.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/name.webp', $result['src']);
        $this->assertStringContainsString('name@2x.webp 1024w', $result['srcset']);
    }

    public function testBuildTopoImageSrcsetHighSuffixStripped(): void
    {
        $url = '/topos/name-high.webp';
        $result = $this->parser->buildTopoImageSrcset($url);
        $this->assertSame('https://www.munichclimbs.de/build/images/topos/name.webp', $result['src']);
        $this->assertStringContainsString('name@2x.webp 1024w', $result['srcset']);
    }
}
