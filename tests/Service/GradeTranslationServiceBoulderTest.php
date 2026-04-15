<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GradeTranslationService;
use PHPUnit\Framework\TestCase;

final class GradeTranslationServiceBoulderTest extends TestCase
{
    public function testFontainebleauToFrench(): void
    {
        self::assertSame('7b+', GradeTranslationService::fontainebleauToFrench('FB 7A'));
        self::assertNull(GradeTranslationService::fontainebleauToFrench('7a'));
        self::assertNull(GradeTranslationService::fontainebleauToFrench(null));
    }

    public function testBoulderGradeToNumberAlignsWithFrenchEquivalent(): void
    {
        $service = new GradeTranslationService();

        self::assertSame($service->gradeToNumber('7b+'), $service->gradeToNumber('FB 7A'));
        self::assertSame($service->gradeToNumber('9b+'), $service->gradeToNumber('FB 9A'));
        self::assertSame(61, $service->gradeToNumber('FB 9A'));
    }
}
