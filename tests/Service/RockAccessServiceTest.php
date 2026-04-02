<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Rock;
use App\Entity\Routes;
use App\Entity\Topo;
use App\Entity\User;
use App\Entity\Videos;
use App\Service\RockAccessService;
use PHPUnit\Framework\TestCase;

final class RockAccessServiceTest extends TestCase
{
    private RockAccessService $service;

    protected function setUp(): void
    {
        $this->service = new RockAccessService();
    }

    public function testSuperAdminIsNotScoped(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ROCK_EDITOR']);

        $this->assertFalse($this->service->isRockScoped($user));
        $this->assertNull($this->service->getEditableRockIds($user));
    }

    public function testRockEditorWithoutSuperAdminIsScoped(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ROCK_EDITOR']);

        $this->assertTrue($this->service->isRockScoped($user));
    }

    public function testAdminWithoutRockEditorRoleIsUnrestricted(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertFalse($this->service->isRockScoped($user));
        $this->assertNull($this->service->getEditableRockIds($user));
    }

    public function testNullUserIsNotRockScoped(): void
    {
        $this->assertFalse($this->service->isRockScoped(null));
        $this->assertTrue($this->service->bypassesRockScope(null));
    }

    public function testScopedUserWithAssignedRockCanEdit(): void
    {
        $rock = new Rock();
        $reflection = new \ReflectionClass($rock);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($rock, 42);

        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ROCK_EDITOR']);
        $user->addEditableRock($rock);

        $this->assertSame([42], $this->service->getEditableRockIds($user));
        $this->assertTrue($this->service->canEditRock($user, $rock));
        $this->assertTrue($this->service->canEditTopo($user, $this->makeTopoForRock($rock)));
    }

    public function testScopedUserCannotEditUnassignedRock(): void
    {
        $assigned = new Rock();
        $other = new Rock();
        foreach ([$assigned, $other] as $i => $r) {
            $reflection = new \ReflectionClass($r);
            $prop = $reflection->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($r, 10 + $i);
        }

        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ROCK_EDITOR']);
        $user->addEditableRock($assigned);

        $this->assertFalse($this->service->canEditRock($user, $other));
        $this->assertFalse($this->service->canEditRoute($user, $this->makeRouteForRock($other)));
    }

    public function testVideoAccessViaRouteRock(): void
    {
        $rock = new Rock();
        $reflection = new \ReflectionClass($rock);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($rock, 7);

        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ROCK_EDITOR']);
        $user->addEditableRock($rock);

        $route = $this->makeRouteForRock($rock);
        $video = new Videos();
        $video->setVideoRoutes($route);

        $this->assertTrue($this->service->canEditVideo($user, $video));
    }

    private function makeTopoForRock(Rock $rock): Topo
    {
        $topo = new Topo();
        $topo->setRocks($rock);

        return $topo;
    }

    private function makeRouteForRock(Rock $rock): Routes
    {
        $route = new Routes();
        $route->setRock($rock);

        return $route;
    }
}
