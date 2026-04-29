<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Topo;
use App\Service\TopoPathEditorSaveService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TopoPathEditorSaveServiceTest extends TestCase
{
    public function testSavePhpLiteralTrimsAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new TopoPathEditorSaveService($em);
        $topo = new Topo();
        $service->savePhpLiteral($topo, "  ['d' => 'M1,2']  \n");

        $this->assertSame("['d' => 'M1,2']", $topo->getPathCollection());
    }

    public function testCsrfIntentConstant(): void
    {
        $this->assertSame('topo_save_topo_path_helper', TopoPathEditorSaveService::CSRF_INTENT);
    }
}
