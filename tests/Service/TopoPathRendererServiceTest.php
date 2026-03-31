<?php

namespace App\Tests\Service;

use App\Service\TopoPathRendererService;
use PHPUnit\Framework\TestCase;

class TopoPathRendererServiceTest extends TestCase
{
    private TopoPathRendererService $service;

    protected function setUp(): void
    {
        $this->service = new TopoPathRendererService();
    }

    public function testParsePhpLiteralTwoPathsNoNewline(): void
    {
        $phpLiteral = "['d' => 'm10,20', 'color' => '#a'], ['d' => 'm30,40']";
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('parsePhpPathArrayLiteral');
        $method->setAccessible(true);
        $paths = $method->invoke($this->service, $phpLiteral);
        $this->assertNotNull($paths);
        $this->assertCount(2, $paths, 'Expected 2 path configs');
        $this->assertSame('m10,20', $paths[0]['d']);
        $this->assertSame('#a', $paths[0]['color'] ?? null);
        $this->assertSame('m30,40', $paths[1]['d']);
    }

    public function testParsePhpLiteralThreePathsMinimal(): void
    {
        $phpLiteral = "['d' => 'a', 'color' => '#x', 'dot' => true], ['d' => 'b'], ['d' => 'c']";
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('parsePhpPathArrayLiteral');
        $method->setAccessible(true);
        $paths = $method->invoke($this->service, $phpLiteral);
        $this->assertNotNull($paths);
        $this->assertCount(3, $paths);
    }

    public function testParsePhpLiteralDValueWithComma(): void
    {
        $phpLiteral = "['d' => 'm1,2', 'color' => '#x'], ['d' => 'b']";
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('parsePhpPathArrayLiteral');
        $method->setAccessible(true);
        $paths = $method->invoke($this->service, $phpLiteral);
        $this->assertNotNull($paths);
        $this->assertCount(2, $paths);
        $this->assertSame('m1,2', $paths[0]['d']);
    }

    public function testParsePhpLiteralExactTwoSegments(): void
    {
        $phpLiteral = "['d' => 'm251,766c8,-20 15,-37', 'color' => '#a16207', 'dot' => true], ['d' => 'm312,552c-7,-11 -9,-22', 'color' => '#a16207', 'dashed' => true]";
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('parsePhpPathArrayLiteral');
        $method->setAccessible(true);
        $paths = $method->invoke($this->service, $phpLiteral);
        $this->assertNotNull($paths);
        $this->assertCount(2, $paths);
        $this->assertSame('m251,766c8,-20 15,-37', $paths[0]['d']);
        $this->assertSame('m312,552c-7,-11 -9,-22', $paths[1]['d']);
    }

    public function testParsePhpLiteralMultiplePaths(): void
    {
        // PHP array literal as stored in DB: multiple path arrays separated by comma/newline
        $phpLiteral = "['d' => 'm251,766c8,-20 15,-37', 'color' => '#a16207', 'dot' => true],\n"
            . "['d' => 'm312,552c-7,-11 -9,-22', 'color' => '#a16207', 'dashed' => true],\n"
            . "['d' => 'm100,200l50,0', 'color' => '#b91c1c']";
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('parsePhpPathArrayLiteral');
        $method->setAccessible(true);
        $paths = $method->invoke($this->service, $phpLiteral);
        $this->assertNotNull($paths, 'Parser should return non-null');
        $this->assertCount(3, $paths, 'Parser should return exactly 3 path configs');
        $svg = $this->service->resolvePathsOverlay('', $phpLiteral);
        $this->assertStringContainsString('id="border_1"', $svg);
        $this->assertStringContainsString('id="border_2"', $svg);
        $this->assertStringContainsString('id="border_3"', $svg);
        $this->assertStringContainsString('data-path-id="1"', $svg);
        $this->assertStringContainsString('data-path-id="2"', $svg);
        $this->assertStringContainsString('data-path-id="3"', $svg);
        $this->assertStringContainsString('route-path-hit', $svg);
        $countBorder = substr_count($svg, 'id="border_');
        $this->assertSame(3, $countBorder, 'Expected exactly 3 border paths');
    }

    public function testParsePhpLiteralTwentyOnePaths(): void
    {
        $paths = [
            ['d' => 'm251,766c8,-20 15,-37 22,-54', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm312,552c-7,-11 -9,-22', 'color' => '#a16207', 'dashed' => true],
            ['d' => 'm340.6729,240.00477c0,-20', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm288,774c7,-25 14,-77', 'color' => '#a16207', 'dashed' => true],
            ['d' => 'm310,785c4,-29 10,-73', 'color' => '#a16207'],
            ['d' => 'm424.3334,135.33337c11,-8', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm331,796c2,-18 9,-35', 'dot' => true],
            ['d' => 'm395.66657,344.66692c-9,-11', 'dot' => true, 'dashed' => true],
            ['d' => 'm391,805c3,-13 3,-29', 'dot' => true],
            ['d' => 'm455,813c-1,-21', 'dashed' => true],
            ['d' => 'm349,479c14,-7 37,-11', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm477,348c-13,-11'],
            ['d' => 'm370.00644,436.00621c-12', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm576,790c3,-39', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm594,497c13,-14', 'color' => '#a16207', 'dot' => true, 'dashed' => true],
            ['d' => 'm619,446c-1,-11', 'dot' => true],
            ['d' => 'm634.00838,781.34208c0,-36', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm634.00838,524.00686c0,-25', 'color' => '#a16207', 'dashed' => true],
            ['d' => 'm673,770c-1,-19'],
            ['d' => 'm712,759c0,-25'],
            ['d' => 'm744,753c0,-20', 'color' => '#a16207', 'dot' => true],
        ];
        // Simulate DB string: PHP array literal with newlines between items
        $phpLiteral = '';
        foreach ($paths as $i => $p) {
            if ($i > 0) {
                $phpLiteral .= ",\n";
            }
            $phpLiteral .= "['d' => '" . str_replace("'", "\\'", $p['d']) . "'";
            if (!empty($p['color'])) {
                $phpLiteral .= ", 'color' => '" . $p['color'] . "'";
            }
            if (!empty($p['dot'])) {
                $phpLiteral .= ", 'dot' => true";
            }
            if (!empty($p['dashed'])) {
                $phpLiteral .= ", 'dashed' => true";
            }
            $phpLiteral .= ']';
        }
        $svg = $this->service->resolvePathsOverlay('', $phpLiteral);
        $this->assertStringContainsString('id="border_21"', $svg);
        $countBorder = substr_count($svg, 'id="border_');
        $this->assertSame(21, $countBorder, 'Expected exactly 21 border paths from PHP literal');
    }

    public function testRenderPathsToSvgArrayInput(): void
    {
        $paths = [
            ['d' => 'm10,20l30,0', 'color' => '#a16207', 'dot' => true],
            ['d' => 'm40,50l10,0', 'color' => '#b91c1c'],
        ];
        $svg = $this->service->renderPathsToSvg($paths);
        $this->assertStringContainsString('id="border_1"', $svg);
        $this->assertStringContainsString('id="border_2"', $svg);
        $this->assertStringContainsString('id="dot"', $svg);
        $this->assertStringContainsString('route-path-hit', $svg);
        $this->assertSame(2, substr_count($svg, 'id="border_'));
    }
}
