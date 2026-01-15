<?php

namespace App\Tests\Service;

use App\Repository\AreaRepository;
use App\Service\AreasService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AreasServiceTest extends TestCase
{
    private AreasService $service;
    private MockObject&AreaRepository $areaRepository;
    private MockObject&CacheInterface $cache;

    protected function setUp(): void
    {
        $this->areaRepository = $this->createMock(AreaRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->service = new AreasService(
            $this->areaRepository,
            $this->cache
        );
    }

    public function testGetAreasInformationReturnsCachedData(): void
    {
        $expectedAreas = [
            ['areaId' => 1, 'name' => 'Area 1', 'slug' => 'area-1', 'rocks' => 5, 'routes' => 50],
            ['areaId' => 2, 'name' => 'Area 2', 'slug' => 'area-2', 'rocks' => 3, 'routes' => 30],
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('areas_information', $this->isType('callable'))
            ->willReturn($expectedAreas);

        $result = $this->service->getAreasInformation();

        $this->assertEquals($expectedAreas, $result);
    }

    public function testGetAreasInformationCallsRepositoryOnCacheMiss(): void
    {
        $expectedAreas = [
            ['areaId' => 1, 'name' => 'Area 1', 'slug' => 'area-1', 'rocks' => 5, 'routes' => 50],
        ];

        $this->areaRepository
            ->expects($this->once())
            ->method('getAreasInformation')
            ->willReturn($expectedAreas);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('areas_information', $this->isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600); // 1 hour TTL
                return $callback($item);
            });

        $result = $this->service->getAreasInformation();

        $this->assertEquals($expectedAreas, $result);
    }

    public function testGetFooterAreasReturnsCachedData(): void
    {
        $expectedAreas = [
            ['areaId' => 1, 'name' => 'Area 1', 'slug' => 'area-1'],
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('areas_footer', $this->isType('callable'))
            ->willReturn($expectedAreas);

        $result = $this->service->getFooterAreas();

        $this->assertEquals($expectedAreas, $result);
    }

    public function testGetFooterAreasCallsRepositoryOnCacheMiss(): void
    {
        $expectedAreas = [
            ['areaId' => 1, 'name' => 'Area 1', 'slug' => 'area-1'],
        ];

        $this->areaRepository
            ->expects($this->once())
            ->method('getAreasFooter')
            ->willReturn($expectedAreas);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('areas_footer', $this->isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600); // 1 hour TTL
                return $callback($item);
            });

        $result = $this->service->getFooterAreas();

        $this->assertEquals($expectedAreas, $result);
    }

    public function testGetSidebarAreasReturnsCachedData(): void
    {
        $expectedAreas = [
            ['id' => 1, 'name' => 'Area 1', 'slug' => 'area-1', 'rocks' => []],
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('areas_sidebar', $this->isType('callable'))
            ->willReturn($expectedAreas);

        $result = $this->service->getSidebarAreas();

        $this->assertEquals($expectedAreas, $result);
    }

    public function testGetSidebarAreasCallsRepositoryOnCacheMiss(): void
    {
        $expectedAreas = [
            ['id' => 1, 'name' => 'Area 1', 'slug' => 'area-1', 'rocks' => []],
        ];

        $this->areaRepository
            ->expects($this->once())
            ->method('sidebarNavigation')
            ->willReturn($expectedAreas);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('areas_sidebar', $this->isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600); // 1 hour TTL
                return $callback($item);
            });

        $result = $this->service->getSidebarAreas();

        $this->assertEquals($expectedAreas, $result);
    }

    public function testClearCacheDeletesAllCacheKeys(): void
    {
        $this->cache
            ->expects($this->exactly(3))
            ->method('delete')
            ->willReturnCallback(function (string $key): bool {
                $this->assertContains($key, [
                    'areas_information',
                    'areas_footer',
                    'areas_sidebar',
                ]);
                return true;
            });

        $this->service->clearCache();
    }

    public function testGetAllAreasDoesNotUseCache(): void
    {
        $expectedAreas = [new \stdClass(), new \stdClass()];

        $this->areaRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedAreas);

        // Cache should NOT be called for getAllAreas
        $this->cache
            ->expects($this->never())
            ->method('get');

        $result = $this->service->getAllAreas();

        $this->assertEquals($expectedAreas, $result);
    }

    public function testGetAreaBySlugDoesNotUseCache(): void
    {
        $expectedArea = new \stdClass();

        $this->areaRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['slug' => 'test-area'])
            ->willReturn($expectedArea);

        // Cache should NOT be called for getAreaBySlug
        $this->cache
            ->expects($this->never())
            ->method('get');

        $result = $this->service->getAreaBySlug('test-area');

        $this->assertEquals($expectedArea, $result);
    }

    public function testGetAreasInformationReturnsEmptyArrayWhenNoAreas(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('areas_information', $this->isType('callable'))
            ->willReturn([]);

        $result = $this->service->getAreasInformation();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

