<?php

namespace App\Service;

class GradeTranslationService
{
    /**
     * Maps grade strings to numeric values
     */
    private const GRADE_MAPPING = [
        '0' => 500,
        '1' => 1,
        '2-' => 2,
        '2' => 3,
        '2+' => 4,
        '3-' => 5,
        '3' => 6,
        '3+' => 7,
        '4-' => 8,
        '4' => 9,
        '4a' => 10,
        '4b' => 11,
        '4c' => 12,
        '4c+' => 13,
        '4+' => 10,
        '5-' => 11,
        '5' => 12,
        '5/5+' => 13,
        '5+' => 14,
        '5+/6-' => 15,
        '5a' => 14,
        '5a+' => 15,
        '5b' => 16,
        '5b+' => 17,
        '5c' => 18,
        '5c+' => 19,
        '6-' => 16,
        '6-/6' => 17,
        '6' => 18,
        '6/6+' => 19,
        '6+' => 20,
        '6+/7-' => 21,
        '6a' => 20,
        '6a/6a+' => 21,
        '6a+' => 22,
        '6a+/6b' => 23,
        '6b' => 24,
        '6b/6b+' => 25,
        '6b+' => 27,
        '6c' => 28,
        '6c+' => 30,
        '6c+/7a' => 31,
        '7-' => 22,
        '7-/7' => 23,
        '7' => 24,
        '7/7+' => 25,
        '7+' => 27,
        '7+/8-' => 28,
        '7a' => 32,
        '7a/7a+' => 33,
        '7a+' => 35,
        '7b' => 36,
        '7b+' => 37,
        '7b+/7c' => 39,
        '7c' => 40,
        '7c/7c+' => 41,
        '7c+' => 43,
        '8-' => 30,
        '8-/8' => 31,
        '8' => 32,
        '8/8+' => 33,
        '8+' => 35,
        '8+/9-' => 36,
        '8a' => 44,
        '8a/8a+' => 45,
        '8a+' => 46,
        '8a+/8b' => 47,
        '8b' => 48,
        '8b/8b+' => 50,
        '8b+' => 51,
        '8b+/8c' => 52,
        '8c' => 54,
        '8c+' => 55,
        '8c+/9a' => 56,
        '9-' => 37,
        '9-/9' => 39,
        '9' => 40,
        '9/9+' => 41,
        '9+' => 43,
        '9+/10-' => 44,
        '9a' => 57,
        '9a/9a+' => 58,
        '9a+' => 59,
        '9b' => 60,
        '9b+' => 61,
        '5a/5b+' => 16,
        '6b+/6c' => 28,
        '7a+/7b' => 35,
        '7c+/8a' => 43,
        'FB 3' => 16,
        'FB 4-' => 18,
        'FB 4' => 21,
        'FB 4+' => 23,
        'FB 5' => 25,
        'FB 5+' => 28,
        'FB 6A' => 30,
        'FB 6A+' => 32,
        'FB 6B' => 33,
        'FB 6B+' => 35,
        'FB 6C' => 35,
        'FB 6C+' => 36,
        'FB 7A' => 37,
        'FB 7A+' => 39,
        'FB 7B' => 41,
        'FB 7B+' => 43,
        'FB 7C' => 45,
        'FB 7C+' => 48,
        'FB 8A' => 51,
        'FB 8A+' => 54,
        'FB 8B' => 55,
        'FB 8B+' => 57,
        'FB 8C' => 59,
        'FB 8C+' => 60,
        'FB 9A' => 61,
        '10-' => 46,
        '10-/10' => 47,
        '10' => 48,
        '10/10+' => 50,
        '10+' => 51,
        '10+/11-' => 52,
        '11-' => 54,
        '11-/11' => 55,
        '11' => 57,
    ];

    /**
     * Maps each known grade string to a UIAA-style chart column (3–11) for rock list histograms.
     * null = excluded from these columns (projects, very easy, or unmapped).
     *
     * @var array<string, int|null>
     */
    private const GRADE_TO_UIAA_CHART_BUCKET = [
        '0' => null,
        '1' => null,
        '2-' => null,
        '2' => null,
        '2+' => null,
        '3-' => 3,
        '3' => 3,
        '3+' => 3,
        '4-' => 4,
        '4' => 4,
        '4a' => 4,
        '4b' => 4,
        '4c' => 4,
        '4c+' => 4,
        '4+' => 4,
        '5-' => 5,
        '5' => 5,
        '5/5+' => 5,
        '5+' => 5,
        '5+/6-' => 6,
        '5a' => 5,
        '5a+' => 6,
        '5b' => 6,
        '5b+' => 6,
        '5c' => 6,
        '5c+' => 6,
        '6-' => 6,
        '6-/6' => 6,
        '6' => 6,
        '6/6+' => 6,
        '6+' => 6,
        '6+/7-' => 7,
        '6a' => 6,
        '6a/6a+' => 6,
        '6a+' => 6,
        '6a+/6b' => 6,
        '6b' => 6,
        '6b/6b+' => 7,
        '6b+' => 7,
        '6c' => 7,
        '6c+' => 7,
        '6c+/7a' => 7,
        '7-' => 7,
        '7-/7' => 7,
        '7' => 7,
        '7/7+' => 7,
        '7+' => 7,
        '7+/8-' => 8,
        '7a' => 7,
        '7a/7a+' => 7,
        '7a+' => 7,
        '7b' => 7,
        '7b+' => 7,
        '7b+/7c' => 8,
        '7c' => 8,
        '7c/7c+' => 8,
        '7c+' => 8,
        '8-' => 8,
        '8-/8' => 8,
        '8' => 8,
        '8/8+' => 8,
        '8+' => 8,
        '8+/9-' => 9,
        '8a' => 8,
        '8a/8a+' => 8,
        '8a+' => 8,
        '8a+/8b' => 8,
        '8b' => 8,
        '8b/8b+' => 9,
        '8b+' => 9,
        '8b+/8c' => 9,
        '8c' => 9,
        '8c+' => 9,
        '8c+/9a' => 9,
        '9-' => 9,
        '9-/9' => 9,
        '9' => 9,
        '9/9+' => 9,
        '9+' => 9,
        '9+/10-' => 10,
        '9a' => 9,
        '9a/9a+' => 10,
        '9a+' => 10,
        '10-' => 10,
        '10-/10' => 10,
        '10' => 10,
        '10/10+' => 10,
        '10+' => 10,
        '10+/11-' => 11,
        '11-' => 11,
        '11-/11' => 11,
        '11' => 11,
        '9b' => 10,
        '9b+' => 11,
        '5a/5b+' => 5,
        '6b+/6c' => 7,
        '7a+/7b' => 7,
        '7c+/8a' => 8,
        // Fontainebleau: chart columns are UIAA 3–11; buckets follow “feel” vs UIAA, not the French-sport row (8a would sit in column 8).
        'FB 3' => 5,
        'FB 4-' => 6,
        'FB 4' => 6,
        'FB 4+' => 6,
        'FB 5' => 7,
        'FB 5+' => 7,
        'FB 6A' => 7,
        'FB 6A+' => 7,
        'FB 6B' => 7,
        'FB 6B+' => 7,
        'FB 6C' => 8,
        'FB 6C+' => 8,
        'FB 7A' => 8,
        'FB 7A+' => 9,
        'FB 7B' => 9,
        'FB 7B+' => 9,
        'FB 7C' => 10,
        'FB 7C+' => 10,
        'FB 8A' => 10,
        'FB 8A+' => 11,
        'FB 8B' => 11,
        'FB 8B+' => 11,
        'FB 8C' => 11,
        'FB 8C+' => 11,
        'FB 9A' => 11,
    ];

    /**
     * Fontainebleau boulder grade → approximate French sport grade (for labels / conversion).
     *
     * @var array<string, string>
     */
    private const FONTAINEBLEAU_GRADE_TO_FRENCH = [
        'FB 3' => '5a/5b+',
        'FB 4-' => '5c',
        'FB 4' => '6a/6a+',
        'FB 4+' => '6a+/6b',
        'FB 5' => '6b/6b+',
        'FB 5+' => '6b+/6c',
        'FB 6A' => '6c+',
        'FB 6A+' => '7a',
        'FB 6B' => '7a/7a+',
        'FB 6B+' => '7a+',
        'FB 6C' => '7a+/7b',
        'FB 6C+' => '7b',
        'FB 7A' => '7b+',
        'FB 7A+' => '7b+/7c',
        'FB 7B' => '7c/7c+',
        'FB 7B+' => '7c+/8a',
        'FB 7C' => '8a/8a+',
        'FB 7C+' => '8b',
        'FB 8A' => '8b+',
        'FB 8A+' => '8c',
        'FB 8B' => '8c+',
        'FB 8B+' => '9a',
        'FB 8C' => '9a+',
        'FB 8C+' => '9b',
        'FB 9A' => '9b+',
    ];

    /**
     * Hueco (V) and YDS sport approximations aligned with Wikipedia’s “Comparison bouldering” table (2016 CAI / IFSC-style);
     * “+” Font steps without a dedicated Wikipedia row use a slash band (e.g. V3/V4).
     *
     * @var array<string, array{hueco: string, yds: string}>
     */
    private const BOULDER_HUECO_YDS = [
        'FB 3' => ['hueco' => '—', 'yds' => '—'],
        'FB 4-' => ['hueco' => 'V0−', 'yds' => '5.8'],
        'FB 4' => ['hueco' => 'V0', 'yds' => '5.9'],
        'FB 4+' => ['hueco' => 'V0+', 'yds' => '5.10a/b'],
        'FB 5' => ['hueco' => 'V1', 'yds' => '5.10c/d'],
        'FB 5+' => ['hueco' => 'V2', 'yds' => '5.10d/5.11a/b'],
        'FB 6A' => ['hueco' => 'V3', 'yds' => '5.11c'],
        'FB 6A+' => ['hueco' => 'V3/V4', 'yds' => '≈ 5.11c–5.12a'],
        'FB 6B' => ['hueco' => 'V4', 'yds' => '5.12a'],
        'FB 6B+' => ['hueco' => 'V4/V5', 'yds' => '≈ 5.12a–5.12b'],
        'FB 6C' => ['hueco' => 'V5', 'yds' => '5.12b'],
        'FB 6C+' => ['hueco' => 'V5/V6', 'yds' => '≈ 5.12b–5.12c'],
        'FB 7A' => ['hueco' => 'V6', 'yds' => '5.12c'],
        'FB 7A+' => ['hueco' => 'V7', 'yds' => '5.12d'],
        'FB 7B' => ['hueco' => 'V8', 'yds' => '5.13a'],
        'FB 7B+' => ['hueco' => 'V8/V9', 'yds' => '≈ 5.13a–5.13c'],
        'FB 7C' => ['hueco' => 'V9', 'yds' => '5.13c'],
        'FB 7C+' => ['hueco' => 'V10', 'yds' => '5.13d'],
        'FB 8A' => ['hueco' => 'V11', 'yds' => '5.14a'],
        'FB 8A+' => ['hueco' => 'V12', 'yds' => '5.14b'],
        'FB 8B' => ['hueco' => 'V13', 'yds' => '5.14c'],
        'FB 8B+' => ['hueco' => 'V14', 'yds' => '5.14d'],
        'FB 8C' => ['hueco' => 'V15', 'yds' => '5.15a'],
        'FB 8C+' => ['hueco' => 'V16', 'yds' => '5.15b'],
        'FB 9A' => ['hueco' => 'V17', 'yds' => '5.15c'],
    ];

    /**
     * “Chart: Climbing Grades Compared” from Bergfreunde (UIAA with Arabic numerals, French, Fb, V columns only).
     * {@link https://www.bergfreunde.eu/climbing-grade-calculator/}
     *
     * @var list<array{uiaa: string, french: string, fb: string, v: string}>
     */
    private const BERGFREUNDE_CLIMBING_GRADES_COMPARED = [
        ['uiaa' => '1', 'french' => '1', 'fb' => '1', 'v' => 'VB-'],
        ['uiaa' => '2', 'french' => '2', 'fb' => '1', 'v' => 'VB-'],
        ['uiaa' => '3', 'french' => '3', 'fb' => '1/2', 'v' => 'VB-'],
        ['uiaa' => '4', 'french' => '4', 'fb' => '2', 'v' => 'VB-'],
        ['uiaa' => '4+', 'french' => '4+', 'fb' => '2', 'v' => 'VB-'],
        ['uiaa' => '5-', 'french' => '5a', 'fb' => '2/3', 'v' => 'VB-/VB'],
        ['uiaa' => '5', 'french' => '5a/5b', 'fb' => '3', 'v' => 'VB'],
        ['uiaa' => '5+', 'french' => '5b', 'fb' => '4a', 'v' => 'VB/V0-'],
        ['uiaa' => '6-', 'french' => '5b/5c', 'fb' => '4a/4b', 'v' => 'V0-'],
        ['uiaa' => '6', 'french' => '5c', 'fb' => '4b', 'v' => 'V0-/V0'],
        ['uiaa' => '6+', 'french' => '6a', 'fb' => '4c', 'v' => 'V0'],
        ['uiaa' => '7-', 'french' => '6a+', 'fb' => '5a', 'v' => 'V0+'],
        ['uiaa' => '7', 'french' => '6b', 'fb' => '5b', 'v' => 'V1'],
        ['uiaa' => '7+', 'french' => '6b+', 'fb' => '5c', 'v' => 'V1/V2'],
        ['uiaa' => '7+/8-', 'french' => '6c', 'fb' => '6a', 'v' => 'V2'],
        ['uiaa' => '8-', 'french' => '6c+', 'fb' => '6a+', 'v' => 'V2/V3'],
        ['uiaa' => '8', 'french' => '7a', 'fb' => '6b', 'v' => 'V3'],
        ['uiaa' => '8+', 'french' => '7a+', 'fb' => '6b+', 'v' => 'V3/V4'],
        ['uiaa' => '8+/9-', 'french' => '7b', 'fb' => '6b+/6c', 'v' => 'V4'],
        ['uiaa' => '9-', 'french' => '7b+', 'fb' => '6c', 'v' => 'V4'],
        ['uiaa' => '9', 'french' => '7c', 'fb' => '6c+', 'v' => 'V4/V5'],
        ['uiaa' => '9+', 'french' => '7c+', 'fb' => '7a', 'v' => 'V5'],
        ['uiaa' => '9+/10-', 'french' => '8a', 'fb' => '7a+', 'v' => 'V6'],
        ['uiaa' => '10-', 'french' => '8a/8a+', 'fb' => '7a+/7b', 'v' => 'V6/V7'],
        ['uiaa' => '10-', 'french' => '8a+', 'fb' => '7b', 'v' => 'V7'],
        ['uiaa' => '10', 'french' => '8b', 'fb' => '7b+', 'v' => 'V8'],
        ['uiaa' => '10+', 'french' => '8b+', 'fb' => '7c', 'v' => 'V9'],
        ['uiaa' => '10+/11-', 'french' => '8c', 'fb' => '7c+', 'v' => 'V10'],
        ['uiaa' => '11-', 'french' => '8c+', 'fb' => '7c+/8a', 'v' => 'V10/V11'],
        ['uiaa' => '11', 'french' => '9a', 'fb' => '8a', 'v' => 'V11'],
        ['uiaa' => '11', 'french' => '9a/9a+', 'fb' => '8a+', 'v' => 'V12'],
        ['uiaa' => '11/11+', 'french' => '9a+', 'fb' => '8a+/8b', 'v' => 'V12/V13'],
        ['uiaa' => '11+', 'french' => '9a+/9b', 'fb' => '8b', 'v' => 'V13'],
        ['uiaa' => '11+/12-', 'french' => '9b', 'fb' => '8b+', 'v' => 'V14'],
        ['uiaa' => '12-', 'french' => '9b+', 'fb' => '8c', 'v' => 'V15'],
        ['uiaa' => '12', 'french' => '9c', 'fb' => '8c+', 'v' => 'V16'],
    ];

    /**
     * Grade strings per UIAA chart column (3–11) for SQL IN (...) clauses.
     *
     * @return array<int, list<string>>
     */
    public static function gradesGroupedByUiaaChartBucket(): array
    {
        $grouped = [];
        foreach (self::GRADE_TO_UIAA_CHART_BUCKET as $grade => $bucket) {
            if ($bucket !== null) {
                $grouped[$bucket][] = $grade;
            }
        }
        ksort($grouped);

        return $grouped;
    }

    /**
     * UIAA chart column (3–11) for histograms / topo colors, or null for projects and unmapped grades.
     */
    public static function uiaaChartBucketForGrade(?string $grade): ?int
    {
        if ($grade === null || $grade === '') {
            return null;
        }

        return self::GRADE_TO_UIAA_CHART_BUCKET[$grade] ?? null;
    }

    /**
     * Convert a grade string to its numeric equivalent
     */
    public function gradeToNumber(?string $grade): ?int
    {
        if ($grade === null || $grade === '') {
            return null;
        }

        return self::GRADE_MAPPING[$grade] ?? null;
    }

    /**
     * Get all available grade strings
     */
    public function getAvailableGrades(): array
    {
        return array_keys(self::GRADE_MAPPING);
    }

    /**
     * @return array<string, string> ChoiceField label => stored grade value
     */
    public function getGradeFormChoices(): array
    {
        $choices = [];
        foreach (array_keys(self::GRADE_MAPPING) as $grade) {
            $choices[$grade] = $grade;
        }

        return $choices;
    }

    /**
     * Approximate French sport grade for a Fontainebleau boulder grade, or null if not a boulder grade.
     */
    public static function fontainebleauToFrench(?string $grade): ?string
    {
        if ($grade === null || $grade === '') {
            return null;
        }

        return self::FONTAINEBLEAU_GRADE_TO_FRENCH[$grade] ?? null;
    }

    /**
     * Grade comparison page: Bergfreunde “Climbing Grades Compared” (UIAA with Arabic numerals, French, Fb, V).
     *
     * @return list<array{uiaa: string, french: string, fb: string, v: string}>
     */
    public static function freeClimbingGradeComparisonTable(): array
    {
        $out = [];
        foreach (self::BERGFREUNDE_CLIMBING_GRADES_COMPARED as $row) {
            $out[] = [
                'uiaa' => self::normalizeBergfreundeGradeCell($row['uiaa']),
                'french' => self::normalizeBergfreundeGradeCell($row['french']),
                'fb' => self::fontBoulderGradeLettersUppercase($row['fb']),
                'v' => self::normalizeBergfreundeGradeCell($row['v']),
            ];
        }

        return $out;
    }

    private static function normalizeBergfreundeGradeCell(string $value): string
    {
        $v = str_replace('\\>', '>', $value);
        $v = preg_replace('/\s*\/\s*/', '/', $v) ?? $v;

        return trim(preg_replace('/\s+/', ' ', $v) ?? $v);
    }

    /**
     * Font boulder steps use an uppercase letter after the digit (6A+, 7C+/8A). Slashes and plain numbers unchanged.
     */
    private static function fontBoulderGradeLettersUppercase(string $fb): string
    {
        $fb = self::normalizeBergfreundeGradeCell($fb);
        $segments = explode('/', $fb);
        foreach ($segments as $i => $segment) {
            $s = trim($segment);
            $replaced = preg_replace_callback(
                '/^(\d+)([abc])([+-]?)$/i',
                static fn (array $m): string => $m[1].strtoupper($m[2]).$m[3],
                $s
            );
            $segments[$i] = $replaced ?? $s;
        }

        return implode('/', $segments);
    }

    /**
     * Check if a grade string is valid
     */
    public function isValidGrade(?string $grade): bool
    {
        if ($grade === null || $grade === '') {
            return false;
        }

        return array_key_exists($grade, self::GRADE_MAPPING);
    }
}
