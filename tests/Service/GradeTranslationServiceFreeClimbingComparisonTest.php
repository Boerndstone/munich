<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GradeTranslationService;
use PHPUnit\Framework\TestCase;

final class GradeTranslationServiceFreeClimbingComparisonTest extends TestCase
{
    public function testBergfreundeChartRowCountAndArabicUiaa(): void
    {
        $rows = GradeTranslationService::freeClimbingGradeComparisonTable();
        self::assertCount(36, $rows);

        self::assertSame('1', $rows[0]['uiaa']);
        self::assertSame('1', $rows[0]['french']);
        self::assertSame('1', $rows[0]['fb']);
        self::assertSame('VB-', $rows[0]['v']);

        self::assertSame('7+/8-', $rows[14]['uiaa']);
        self::assertSame('6c', $rows[14]['french']);

        $last = $rows[35];
        self::assertSame('12', $last['uiaa']);
        self::assertSame('9c', $last['french']);
        self::assertSame('8C+', $last['fb']);
        self::assertSame('V16', $last['v']);
    }

    public function testFbColumnUppercasesFontLetters(): void
    {
        $rows = GradeTranslationService::freeClimbingGradeComparisonTable();
        $row8cPlus = $rows[28];
        self::assertSame('8c+', $row8cPlus['french']);
        self::assertSame('7C+/8A', $row8cPlus['fb']);
    }

    public function testDuplicateNumericUiaaRowsStillDifferInFrench(): void
    {
        $rows = GradeTranslationService::freeClimbingGradeComparisonTable();
        $tenMinus = array_values(array_filter($rows, static fn (array $r): bool => $r['uiaa'] === '10-'));
        self::assertCount(2, $tenMinus);
        self::assertSame('8a/8a+', $tenMinus[0]['french']);
        self::assertSame('8a+', $tenMinus[1]['french']);
    }
}
