<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GradeTranslationService;
use App\Service\TopoPathGradeColorService;
use PHPUnit\Framework\TestCase;

final class TopoPathGradeColorServiceTest extends TestCase
{
    private TopoPathGradeColorService $service;

    protected function setUp(): void
    {
        $this->service = new TopoPathGradeColorService();
    }

    public function testStrokeHexForBucketThree(): void
    {
        self::assertSame('#10b981', $this->service->strokeHexForBucket(3));
    }

    public function testStrokeHexForBucketEight(): void
    {
        self::assertSame('#f97316', $this->service->strokeHexForBucket(8));
    }

    public function testStrokeHexForBucketEleven(): void
    {
        self::assertSame('#9f1239', $this->service->strokeHexForBucket(11));
    }

    public function testStrokeHexForNullBucketUsesFallback(): void
    {
        self::assertSame('#000000', $this->service->strokeHexForBucket(null));
    }

    public function testStrokeHexForGradeSixMatchesYellowBucket(): void
    {
        self::assertSame(6, GradeTranslationService::uiaaChartBucketForGrade('6'));
        self::assertSame('#eab308', $this->service->strokeHexForGrade('6'));
    }

    public function testStrokeHexForEmptyGradeIsFallback(): void
    {
        self::assertSame('#000000', $this->service->strokeHexForGrade(''));
        self::assertSame('#000000', $this->service->strokeHexForGrade(null));
    }
}
