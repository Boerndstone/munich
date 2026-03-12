<?php

namespace App\Tests\Service;

use App\Service\RouteGroupingService;
use PHPUnit\Framework\TestCase;

class RouteGroupingServiceTest extends TestCase
{
    private RouteGroupingService $service;

    protected function setUp(): void
    {
        $this->service = new RouteGroupingService();
    }

    public function testGroupRoutesByTopo(): void
    {
        $routes = [
            [
                'topoName' => 'Topo A',
                'topoNumber' => 1,
                'topoImage' => 'https://example.com/topo-a.webp',
                'topoPathCollection' => '<path id="svg_1" d="M0,0"/>',
                'topoViewBox' => '0 0 1024 820',
                'withSector' => true,
                'routeName' => 'Route 1',
                'routeGrade' => '6a',
                'routeRating' => 4,
                'routeProtection' => 'good',
                'rockQuality' => 'excellent',
                'areaId' => 1,
                'rockId' => 1,
                'routeId' => 1,
                'routefirstAscent' => 'John Doe',
                'routeyearFirstAscent' => '2020',
                'routeDescription' => 'Great route',
                'routeComment' => [],
                'videoLink' => 'https://example.com/video1'
            ],
            [
                'topoName' => 'Topo A',
                'topoNumber' => 1,
                'topoImage' => 'https://example.com/topo-a.webp',
                'topoPathCollection' => '<path id="svg_1" d="M0,0"/>',
                'topoViewBox' => '0 0 1024 820',
                'withSector' => false,
                'routeName' => 'Route 2',
                'routeGrade' => '6b',
                'routeRating' => 3,
                'routeProtection' => 'fair',
                'rockQuality' => 'good',
                'areaId' => 1,
                'rockId' => 1,
                'routeId' => 2,
                'routefirstAscent' => 'Jane Smith',
                'routeyearFirstAscent' => '2021',
                'routeDescription' => 'Nice route',
                'routeComment' => [],
                'videoLink' => null
            ],
            [
                'topoName' => 'Topo B',
                'topoNumber' => 2,
                'topoImage' => 'https://example.com/topo-b.webp',
                'topoPathCollection' => '<path id="svg_2" d="M0,0"/>',
                'topoViewBox' => '0 0 1024 820',
                'withSector' => false,
                'routeName' => 'Route 3',
                'routeGrade' => '7a',
                'routeRating' => 5,
                'routeProtection' => 'excellent',
                'rockQuality' => 'outstanding',
                'areaId' => 1,
                'rockId' => 1,
                'routeId' => 3,
                'routefirstAscent' => 'Bob Wilson',
                'routeyearFirstAscent' => '2019',
                'routeDescription' => 'Amazing route',
                'routeComment' => [],
                'videoLink' => 'https://example.com/video3'
            ]
        ];

        $result = $this->service->groupRoutesByTopo($routes);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('Topo A', $result);
        $this->assertArrayHasKey('Topo B', $result);
        
        // Check Topo A has 2 routes
        $this->assertCount(2, $result['Topo A']['routes']);
        $this->assertEquals('Topo A', $result['Topo A']['topoName']);
        $this->assertEquals(1, $result['Topo A']['topoNumber']);
        $this->assertEquals('https://example.com/topo-a.webp', $result['Topo A']['topoImage']);
        $this->assertEquals('<path id="svg_1" d="M0,0"/>', $result['Topo A']['topoPathCollection']);
        $this->assertEquals('0 0 1024 820', $result['Topo A']['topoViewBox']);
        $this->assertTrue($result['Topo A']['withSector']);
        
        // Check Topo B has 1 route
        $this->assertCount(1, $result['Topo B']['routes']);
        $this->assertEquals('Topo B', $result['Topo B']['topoName']);
        $this->assertEquals(2, $result['Topo B']['topoNumber']);
        
        // Check routes are properly structured
        $this->assertEquals('Route 1', $result['Topo A']['routes'][0]['routeName']);
        $this->assertEquals('6a', $result['Topo A']['routes'][0]['routeGrade']);
        $this->assertEquals('Route 2', $result['Topo A']['routes'][1]['routeName']);
        $this->assertEquals('6b', $result['Topo A']['routes'][1]['routeGrade']);
        $this->assertEquals('Route 3', $result['Topo B']['routes'][0]['routeName']);
        $this->assertEquals('7a', $result['Topo B']['routes'][0]['routeGrade']);
    }

    public function testHasSectors(): void
    {
        $topoRoutes = [
            ['withSector' => false],
            ['withSector' => true],
            ['withSector' => false]
        ];

        $this->assertTrue($this->service->hasSectors($topoRoutes));
    }

    public function testHasSectorsFalse(): void
    {
        $topoRoutes = [
            ['withSector' => false],
            ['withSector' => false],
            ['withSector' => null]
        ];

        $this->assertFalse($this->service->hasSectors($topoRoutes));
    }

    public function testGetFirstTopoImage(): void
    {
        $topoRoutes = [
            ['topoImage' => ''],
            ['topoImage' => 'https://example.com/first.webp'],
            ['topoImage' => 'https://example.com/second.webp']
        ];

        $this->assertEquals('https://example.com/first.webp', $this->service->getFirstTopoImage($topoRoutes));
    }

    public function testGetFirstTopoImageNull(): void
    {
        $topoRoutes = [
            ['topoImage' => ''],
            ['topoImage' => null],
            ['topoImage' => '']
        ];

        $this->assertNull($this->service->getFirstTopoImage($topoRoutes));
    }
} 