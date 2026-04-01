<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GradeTranslationService;
use PHPUnit\Framework\TestCase;

final class GradeTranslationServiceUiaaBucketTest extends TestCase
{
    public function testKnownGradesMapToBuckets(): void
    {
        self::assertSame(3, GradeTranslationService::uiaaChartBucketForGrade('3'));
        self::assertSame(7, GradeTranslationService::uiaaChartBucketForGrade('7a'));
        self::assertSame(11, GradeTranslationService::uiaaChartBucketForGrade('11'));
    }

    public function testNullAndEmptyReturnNull(): void
    {
        self::assertNull(GradeTranslationService::uiaaChartBucketForGrade(null));
        self::assertNull(GradeTranslationService::uiaaChartBucketForGrade(''));
    }

    public function testProjectLikeGradeReturnsNull(): void
    {
        self::assertNull(GradeTranslationService::uiaaChartBucketForGrade('0'));
        self::assertNull(GradeTranslationService::uiaaChartBucketForGrade('2'));
    }
}
