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
                'topoSvg' => '<svg>Topo A SVG</svg>',
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
                'topoSvg' => '<svg>Topo A SVG</svg>',
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
                'topoSvg' => '<svg>Topo B SVG</svg>',
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
        $this->assertEquals('<svg>Topo A SVG</svg>', $result['Topo A']['topoSvg']);
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

    public function testGetFirstTopoSvg(): void
    {
        $topoRoutes = [
            ['topoSvg' => ''],
            ['topoSvg' => '<svg>First SVG</svg>'],
            ['topoSvg' => '<svg>Second SVG</svg>']
        ];

        $this->assertEquals('<svg>First SVG</svg>', $this->service->getFirstTopoSvg($topoRoutes));
    }

    public function testGetFirstTopoSvgNull(): void
    {
        $topoRoutes = [
            ['topoSvg' => ''],
            ['topoSvg' => null],
            ['topoSvg' => '']
        ];

        $this->assertNull($this->service->getFirstTopoSvg($topoRoutes));
    }
} 