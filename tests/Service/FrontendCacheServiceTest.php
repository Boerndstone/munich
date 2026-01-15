<?php

namespace App\Tests\Service;

use App\Repository\CommentRepository;
use App\Repository\RockRepository;
use App\Repository\RoutesRepository;
use App\Service\FrontendCacheService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class FrontendCacheServiceTest extends TestCase
{
    private FrontendCacheService $service;
    private MockObject&RoutesRepository $routesRepository;
    private MockObject&CommentRepository $commentRepository;
    private MockObject&RockRepository $rockRepository;
    private MockObject&CacheInterface $cache;

    protected function setUp(): void
    {
        $this->routesRepository = $this->createMock(RoutesRepository::class);
        $this->commentRepository = $this->createMock(CommentRepository::class);
        $this->rockRepository = $this->createMock(RockRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->service = new FrontendCacheService(
            $this->routesRepository,
            $this->commentRepository,
            $this->rockRepository,
            $this->cache
        );
    }

    public function testGetLatestRoutesReturnsCachedData(): void
    {
        $expectedRoutes = [
            ['id' => 1, 'name' => 'Route 1', 'grade' => '6a', 'rockName' => 'Rock A', 'rockSlug' => 'rock-a', 'areaSlug' => 'area-a'],
            ['id' => 2, 'name' => 'Route 2', 'grade' => '7b', 'rockName' => 'Rock B', 'rockSlug' => 'rock-b', 'areaSlug' => 'area-b'],
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_latest_routes', $this->isType('callable'))
            ->willReturn($expectedRoutes);

        $result = $this->service->getLatestRoutes();

        $this->assertEquals($expectedRoutes, $result);
    }

    public function testGetLatestRoutesCallsRepositoryOnCacheMiss(): void
    {
        $expectedRoutes = [
            ['id' => 1, 'name' => 'Route 1', 'grade' => '6a', 'rockName' => 'Rock A', 'rockSlug' => 'rock-a', 'areaSlug' => 'area-a'],
        ];

        $this->routesRepository
            ->expects($this->once())
            ->method('latestRoutes')
            ->willReturn($expectedRoutes);

        // Simulate cache miss by executing the callback
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_latest_routes', $this->isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(1800); // 30 minutes TTL
                return $callback($item);
            });

        $result = $this->service->getLatestRoutes();

        $this->assertEquals($expectedRoutes, $result);
    }

    public function testGetLatestCommentsReturnsCachedData(): void
    {
        $expectedComments = [
            ['username' => 'User1', 'routeName' => 'Route 1', 'commentComment' => 'Great!', 'areaSlug' => 'area-a', 'rockSlug' => 'rock-a'],
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_latest_comments', $this->isType('callable'))
            ->willReturn($expectedComments);

        $result = $this->service->getLatestComments();

        $this->assertEquals($expectedComments, $result);
    }

    public function testGetLatestCommentsCallsRepositoryOnCacheMiss(): void
    {
        $expectedComments = [
            ['username' => 'User1', 'routeName' => 'Route 1', 'commentComment' => 'Great!'],
        ];

        $this->commentRepository
            ->expects($this->once())
            ->method('latestComments')
            ->willReturn($expectedComments);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_latest_comments', $this->isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(600); // 10 minutes TTL
                return $callback($item);
            });

        $result = $this->service->getLatestComments();

        $this->assertEquals($expectedComments, $result);
    }

    public function testGetBannedRocksReturnsCachedData(): void
    {
        $expectedBannedRocks = [
            ['id' => 1, 'name' => 'Banned Rock 1', 'slug' => 'banned-rock-1', 'areaName' => 'Area A', 'areaSlug' => 'area-a'],
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_banned_rocks', $this->isType('callable'))
            ->willReturn($expectedBannedRocks);

        $result = $this->service->getBannedRocks();

        $this->assertEquals($expectedBannedRocks, $result);
    }

    public function testGetBannedRocksCallsRepositoryOnCacheMiss(): void
    {
        $expectedBannedRocks = [
            ['id' => 1, 'name' => 'Banned Rock 1', 'slug' => 'banned-rock-1', 'areaName' => 'Area A', 'areaSlug' => 'area-a'],
        ];

        $this->rockRepository
            ->expects($this->once())
            ->method('saisonalGesperrt')
            ->willReturn($expectedBannedRocks);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_banned_rocks', $this->isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600); // 1 hour TTL
                return $callback($item);
            });

        $result = $this->service->getBannedRocks();

        $this->assertEquals($expectedBannedRocks, $result);
    }

    public function testClearCacheDeletesAllCacheKeys(): void
    {
        $this->cache
            ->expects($this->exactly(3))
            ->method('delete')
            ->willReturnCallback(function (string $key): bool {
                $this->assertContains($key, [
                    'frontend_latest_routes',
                    'frontend_latest_comments',
                    'frontend_banned_rocks',
                ]);
                return true;
            });

        $this->service->clearCache();
    }

    public function testClearCommentsCacheDeletesOnlyCommentsKey(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('frontend_latest_comments')
            ->willReturn(true);

        $this->service->clearCommentsCache();
    }

    public function testClearRoutesCacheDeletesOnlyRoutesKey(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('frontend_latest_routes')
            ->willReturn(true);

        $this->service->clearRoutesCache();
    }

    public function testGetLatestRoutesReturnsEmptyArrayWhenNoRoutes(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_latest_routes', $this->isType('callable'))
            ->willReturn([]);

        $result = $this->service->getLatestRoutes();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetLatestCommentsReturnsEmptyArrayWhenNoComments(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_latest_comments', $this->isType('callable'))
            ->willReturn([]);

        $result = $this->service->getLatestComments();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetBannedRocksReturnsEmptyArrayWhenNoBannedRocks(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('frontend_banned_rocks', $this->isType('callable'))
            ->willReturn([]);

        $result = $this->service->getBannedRocks();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

