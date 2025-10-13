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
