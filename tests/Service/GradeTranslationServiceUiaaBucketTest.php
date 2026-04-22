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
        // French sport: bucket from Bergfreunde (7a ↔ UIAA 8), not the old “leading digit” heuristic.
        self::assertSame(8, GradeTranslationService::uiaaChartBucketForGrade('7a'));
        self::assertSame(11, GradeTranslationService::uiaaChartBucketForGrade('11'));
        self::assertSame(8, GradeTranslationService::uiaaChartBucketForGrade('FB 7A'));
        self::assertSame(10, GradeTranslationService::uiaaChartBucketForGrade('FB 7C'));
        self::assertSame(5, GradeTranslationService::uiaaChartBucketForGrade('FB 3'));
        // Same difficulty rank in GRADE_MAPPING (41): same chart column (French 7c ↔ UIAA 9).
        self::assertSame(9, GradeTranslationService::uiaaChartBucketForGrade('7c/7c+'));
        self::assertSame(9, GradeTranslationService::uiaaChartBucketForGrade('9/9+'));
        self::assertSame(9, GradeTranslationService::uiaaChartBucketForGrade('7c'));
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
